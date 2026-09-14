<?php

namespace App\Console\Commands;

use App\Services\LegacyMembershipImporter;
use Illuminate\Console\Command;

/**
 * Wrapper CLI ponctuel autour de LegacyMembershipImporter (toute la logique
 * de rapprochement des onglets vit dans le service — voir ce fichier pour
 * le détail des décisions actées avec l'utilisateur le 2026-09-11). Le
 * panneau admin dispose aussi d'un bouton "Importer l'historique (Excel)"
 * qui utilise EXACTEMENT le même service (MembershipImportController) —
 * cette commande reste utile pour un import fait directement depuis le
 * terminal du serveur, sans passer par le navigateur.
 *
 * Décisions actées avec l'utilisateur avant écriture :
 *   - L'ID existant (numero_membre) de chaque membre est conservé TEL QUEL.
 *   - Les membres inscrits mais non encore payés sont importés aussi, au
 *     statut `en_attente_paiement`.
 *   - Aucun email automatique n'est envoyé pour un membre importé.
 *   - Par défaut la commande tourne en mode APERÇU (aucune écriture) ; il
 *     faut l'option --commit pour réellement importer.
 *   - Rejouable sans risque : tout numero_membre déjà présent en base est
 *     automatiquement ignoré (pas de doublon possible).
 *   - Depuis le 2026-09-14 : le format des numéros générés par le site/
 *     l'admin (MembershipReferenceGenerator) est ALIGNÉ sur celui des ID
 *     historiques (CI-{année}-{séquence sur 4 chiffres}, ex. CI-2026-0001)
 *     — ce n'était pas le cas avant (le site produisait un format à 6
 *     chiffres). Le prochain numéro est en plus TOUJOURS recalculé à
 *     partir du plus grand numero_membre réellement présent en base au
 *     moment de la génération (pas d'un compteur qui ne fait qu'augmenter)
 *     — un import n'a donc plus besoin de "resynchroniser" quoi que ce
 *     soit après coup, et une suppression libère bien son numéro pour le
 *     prochain membre.
 *
 * ATTENTION CONFIDENTIALITÉ : le fichier Excel source contient des données
 * personnelles. Ne pas le committer dans le dépôt Git. Une fois l'import
 * validé, supprimer le fichier du poste/serveur.
 */
class ImportLegacyMemberships extends Command
{
    protected $signature = 'membership:import-legacy
        {path : Chemin vers le fichier Excel (ex. storage/app/imports/adhesions.xlsx)}
        {--commit : Écrit réellement en base. Sans cette option, la commande ne fait qu\'un aperçu (dry-run).}';

    protected $description = 'Importe une fois pour toutes les adhérents historiques (pré-site) depuis le fichier Excel Google Forms.';

    public function __construct(private readonly LegacyMembershipImporter $importer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->argument('path');
        $commit = (bool) $this->option('commit');

        $this->info($commit
            ? '=== MODE IMPORT RÉEL (--commit) : les écritures ci-dessous seront enregistrées en base ==='
            : '=== MODE APERÇU (dry-run) : aucune écriture en base. Relancez avec --commit pour importer réellement. ===');

        try {
            $plan = $this->importer->plan($path);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Indicateur', 'Nombre'],
            [
                ['Membres à créer', $plan['counts']['to_create']],
                ['  dont validés (validee)', $plan['counts']['validees']],
                ['  dont en attente de paiement', $plan['counts']['en_attente']],
                ['Déjà présents en base (ignorés)', $plan['counts']['skipped_existing']],
                ['Avertissements (données incomplètes)', $plan['counts']['warnings']],
            ]
        );

        if (! empty($plan['warnings'])) {
            $this->newLine();
            $this->warn('Avertissements (aucune donnée personnelle affichée) :');
            foreach ($plan['warnings'] as $w) {
                $this->line("  - {$w}");
            }
        }

        if (! $commit) {
            $this->newLine();
            $this->info('Aperçu terminé. Relancez avec --commit pour écrire ces '.$plan['counts']['to_create'].' membre(s) en base.');

            return self::SUCCESS;
        }

        if (empty($plan['to_create'])) {
            $this->info('Rien à importer.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Confirmer l\'écriture de '.$plan['counts']['to_create'].' membre(s) en base ? Aucun email ne sera envoyé.', true)) {
            $this->info('Import annulé.');

            return self::SUCCESS;
        }

        $result = $this->importer->commit($plan['to_create']);

        $this->newLine();
        $this->info("{$result['created']} membre(s) importé(s) avec succès.");

        if (! empty($result['failed'])) {
            $this->error(count($result['failed']).' ligne(s) en échec :');
            foreach ($result['failed'] as $f) {
                $this->line("  - {$f}");
            }
        }

        $this->newLine();
        $this->warn('Rappel confidentialité : pensez à supprimer le fichier Excel source ('.$path.') une fois l\'import vérifié.');

        return empty($result['failed']) ? self::SUCCESS : self::FAILURE;
    }
}
