<?php

namespace App\Services;

use App\Enums\ContributionDomain;
use App\Enums\ContributionType;
use App\Enums\MembershipRegion;
use App\Enums\MembershipStatus;
use App\Models\Membership;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Logique PARTAGÉE d'import des adhérents historiques (pré-site), utilisée
 * à la fois par la commande Artisan `membership:import-legacy` (usage
 * ponctuel en ligne de commande) et par MembershipImportController (bouton
 * "Importer l'historique (Excel)" du panneau admin — accord explicite du
 * 2026-09-11 : les deux points d'entrée doivent produire EXACTEMENT le même
 * résultat, d'où cette extraction dans un service unique plutôt qu'une
 * duplication de la logique de rapprochement des deux onglets.
 *
 * Voir la commande Artisan pour le détail des décisions actées avec
 * l'utilisateur (conservation de l'ID existant, statut déduit de la colonne
 * Validation de l'onglet "Cartes", aucun email automatique, etc.).
 */
class LegacyMembershipImporter
{
    /** @var array<string,string> Libellé Excel -> valeur enum MembershipRegion */
    private const REGION_MAP = [
        'Ziguinchor' => MembershipRegion::Ziguinchor,
        'Sédhiou' => MembershipRegion::Sedhiou,
        'Kolda' => MembershipRegion::Kolda,
        'Dakar' => MembershipRegion::Dakar,
        'Diaspora' => MembershipRegion::Diaspora,
    ];

    /** @var array<string,string> Libellé Excel -> valeur enum ContributionType */
    private const TYPE_CONTRIBUTION_MAP = [
        'Membre actif' => ContributionType::MembreActif,
        'Bénévole ponctuel' => ContributionType::BenevolePonctuel,
        'Expert / Conseiller technique' => ContributionType::ExpertConseillerTechnique,
    ];

    /** @var array<string,string> Libellé Excel -> valeur enum ContributionDomain */
    private const DOMAINE_MAP = [
        'Pôle Capital Humain' => ContributionDomain::PoleCapitalHumain,
        'Pôle Économie, Agriculture & Attractivité' => ContributionDomain::PoleEconomieAgricultureAttractivite,
        'Pôle Culture & Communication' => ContributionDomain::PoleCultureCommunication,
        'Pôle Support' => ContributionDomain::PoleSupport,
        'Commission scientifique' => ContributionDomain::CommissionScientifique,
        'Coordination régionale' => ContributionDomain::CoordinationRegionale,
        'Comité des sages' => ContributionDomain::ComiteDesSages,
    ];

    /**
     * Analyse le fichier Excel et calcule ce qui SERAIT importé, sans rien
     * écrire en base. `warnings` ne contient jamais de donnée personnelle
     * (seulement des ID + libellés d'anomalie) — sûr à renvoyer tel quel
     * dans une réponse API.
     *
     * @return array{
     *   to_create: array<int,array<string,mixed>>,
     *   skipped_existing: array<int,string>,
     *   warnings: array<int,string>,
     *   counts: array{to_create:int,validees:int,en_attente:int,skipped_existing:int,warnings:int},
     * }
     *
     * @throws \RuntimeException si le fichier est illisible ou si un onglet requis est absent.
     */
    public function plan(string $path): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException("Fichier introuvable : {$path}");
        }

        if (! class_exists(IOFactory::class)) {
            throw new \RuntimeException('Le package phpoffice/phpspreadsheet est requis (composer require phpoffice/phpspreadsheet).');
        }

        $spreadsheet = IOFactory::load($path);

        $cartes = $this->readSheet($spreadsheet, 'Cartes');
        $formResponses = $this->readSheet($spreadsheet, 'Form_Responses');

        if ($cartes === null || $formResponses === null) {
            throw new \RuntimeException('Les onglets "Cartes" et "Form_Responses" sont tous les deux requis dans le fichier.');
        }

        // Groupe les lignes Form_Responses par ID Membre (une même ID peut
        // avoir plusieurs lignes — voir pickBestFormRow()).
        $formByIdMembre = [];
        foreach ($formResponses as $row) {
            $id = trim((string) ($row['ID Membre'] ?? ''));
            if ($id === '') {
                continue;
            }
            $formByIdMembre[$id][] = $row;
        }

        $toCreate = [];
        $skippedExisting = [];
        $warnings = [];

        foreach ($cartes as $carteRow) {
            $id = trim((string) ($carteRow['ID_Membre'] ?? ''));
            if ($id === '') {
                continue;
            }

            if (Membership::query()->where('numero_membre', $id)->exists()) {
                $skippedExisting[] = $id;

                continue;
            }

            $validation = $this->toBool($carteRow['Validation'] ?? false);
            $formRow = $this->pickBestFormRow($formByIdMembre[$id] ?? [], $validation);

            if ($formRow === null) {
                $warnings[] = "{$id} : aucune ligne correspondante dans Form_Responses — import limité aux champs de l'onglet Cartes.";
            }

            [$attributes, $rowWarnings] = $this->buildAttributes($id, $carteRow, $formRow, $validation);
            foreach ($rowWarnings as $w) {
                $warnings[] = "{$id} : {$w}";
            }

            $toCreate[] = $attributes;
        }

        $validees = collect($toCreate)->where('statut', MembershipStatus::Validee->value)->count();
        $enAttente = collect($toCreate)->where('statut', MembershipStatus::EnAttentePaiement->value)->count();

        return [
            'to_create' => $toCreate,
            'skipped_existing' => $skippedExisting,
            'warnings' => $warnings,
            'counts' => [
                'to_create' => count($toCreate),
                'validees' => $validees,
                'en_attente' => $enAttente,
                'skipped_existing' => count($skippedExisting),
                'warnings' => count($warnings),
            ],
        ];
    }

    /**
     * Écrit réellement les membres en base (transaction unique). À appeler
     * avec `to_create` d'un plan() FRAÎCHEMENT recalculé (pas un plan mis en
     * cache côté client) — voir MembershipImportController::commit(), qui
     * relit le fichier pour rester la source de vérité même si d'autres
     * imports ont eu lieu entre l'aperçu et la confirmation.
     *
     * Ne touche plus au compteur de numéros d'adhésion (inutile depuis le
     * 2e correctif du 2026-09-14 : MembershipReferenceGenerator recalcule
     * toujours le prochain numéro à partir de l'état réel de la table, il
     * ne peut donc plus être désynchronisé par un import).
     *
     * @param  array<int,array<string,mixed>>  $toCreate
     * @return array{created:int, failed:array<int,string>}
     */
    public function commit(array $toCreate): array
    {
        $created = 0;
        $failed = [];

        DB::transaction(function () use ($toCreate, &$created, &$failed) {
            foreach ($toCreate as $attributes) {
                try {
                    Membership::create($attributes);
                    $created++;
                } catch (\Throwable $e) {
                    $failed[] = $attributes['numero_membre'].' : '.$e->getMessage();
                }
            }
        });

        return ['created' => $created, 'failed' => $failed];
    }

    /**
     * Lit une feuille en tableau associatif [colonne_entête => valeur] par
     * ligne, à partir des entêtes de la ligne 1. Retourne null si la
     * feuille n'existe pas.
     *
     * @return array<int,array<string,mixed>>|null
     */
    private function readSheet($spreadsheet, string $sheetName): ?array
    {
        if (! $spreadsheet->sheetNameExists($sheetName)) {
            return null;
        }

        $sheet = $spreadsheet->getSheetByName($sheetName);
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return [];
        }

        $headers = array_map(fn ($h) => is_string($h) ? trim($h) : $h, array_shift($rows));

        $result = [];
        foreach ($rows as $row) {
            $assoc = [];
            foreach ($headers as $i => $header) {
                if ($header === null || $header === '') {
                    continue;
                }
                $assoc[$header] = $row[$i] ?? null;
            }
            // Ignore les lignes totalement vides.
            if (count(array_filter($assoc, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }
            $result[] = $assoc;
        }

        return $result;
    }

    /**
     * Parmi les lignes Form_Responses partageant le même ID Membre, choisit
     * celle dont le statut concorde avec la Validation (fiable) de l'onglet
     * Cartes ; à défaut, la plus récente (Horodateur).
     *
     * @param  array<int,array<string,mixed>>  $candidates
     */
    private function pickBestFormRow(array $candidates, bool $validation): ?array
    {
        if (count($candidates) <= 1) {
            return $candidates[0] ?? null;
        }

        $matching = array_filter($candidates, function ($row) use ($validation) {
            $statut = trim((string) ($row['Statut'] ?? ''));

            return $validation
                ? $statut === 'Membre actif'
                : $statut === 'En attente';
        });

        if (count($matching) === 1) {
            return array_values($matching)[0];
        }

        $pool = count($matching) > 0 ? $matching : $candidates;

        usort($pool, function ($a, $b) {
            return $this->toTimestamp($a['Horodateur'] ?? null) <=> $this->toTimestamp($b['Horodateur'] ?? null);
        });

        return end($pool) ?: null;
    }

    /**
     * @return array{0: array<string,mixed>, 1: array<int,string>}
     */
    private function buildAttributes(string $id, array $carteRow, ?array $formRow, bool $validation): array
    {
        $warnings = [];

        $nomComplet = trim((string) ($formRow['Prénom (s) et Nom'] ?? $carteRow['Nom_Complet'] ?? ''));
        if ($nomComplet === '') {
            $warnings[] = 'nom complet manquant';
        }

        $email = trim((string) ($formRow['Adresse e-mail'] ?? $carteRow['Email'] ?? ''));
        if ($email === '') {
            $warnings[] = 'email manquant';
        }

        $telephone = $this->normalizePhone($formRow['Numéro Téléphone / WhatsApp'] ?? null);
        if ($telephone === '') {
            $warnings[] = 'téléphone manquant';
        }

        $regionLabel = trim((string) ($formRow['Région de résidence'] ?? $carteRow['Région'] ?? ''));
        $region = self::REGION_MAP[$regionLabel] ?? null;
        if ($region === null) {
            $warnings[] = "région non reconnue (\"{$regionLabel}\"), ignorée";
        }

        $departement = trim((string) ($formRow['Renseignez votre département en Casamance'] ?? ''));

        [$domaine, $domaineNote] = $this->mapDomaine((string) ($formRow['Dans quels domaines souhaitez-vous contribuer ?'] ?? ''));

        $typeLabel = trim((string) ($formRow['Type de contribution souhaitée'] ?? ''));
        $type = self::TYPE_CONTRIBUTION_MAP[$typeLabel] ?? null;

        $adminNoteParts = ['Importé depuis le fichier Excel historique (adhésion antérieure au site).'];
        if ($domaineNote) {
            $adminNoteParts[] = $domaineNote;
        }
        if (is_string($carteRow['Statut'] ?? null) && str_contains((string) $carteRow['Statut'], 'Erreur')) {
            $adminNoteParts[] = "Statut technique invalide dans le fichier source (\"{$carteRow['Statut']}\") — statut réel déduit de la colonne Validation.";
        }

        $inscritLe = $this->toCarbon($formRow['Horodateur'] ?? $carteRow['Date_Inscription'] ?? null);

        return [
            [
                'numero_membre' => $id,
                'nom_complet' => $nomComplet ?: '(nom non renseigné)',
                'email' => $email ?: "membre-{$id}@a-completer.casaimpact.local",
                'telephone' => $telephone,
                'profession' => $this->nullableTrim($formRow['Profession / Domaine d\'activité'] ?? null),
                'region' => $region?->value ?? MembershipRegion::Ziguinchor->value,
                'departement' => $departement !== '' ? $departement : null,
                'domaine_contribution' => $domaine?->value,
                'type_contribution' => $type?->value,
                'photo_path' => null,
                'engagement_moral' => true,
                'suggestions_competences' => $this->nullableTrim($formRow['Avez-vous des suggestions ou des compétences particulières à mettre à profit ?'] ?? null),
                'statut' => $validation ? MembershipStatus::Validee->value : MembershipStatus::EnAttentePaiement->value,
                'source' => 'import_excel',
                'admin_note' => implode(' ', $adminNoteParts),
                'validated_at' => $validation ? ($inscritLe ?? now()) : null,
                'validated_by' => null,
                'created_at' => $inscritLe ?? now(),
                'updated_at' => now(),
            ],
            $warnings,
        ];
    }

    /**
     * @return array{0: ?ContributionDomain, 1: ?string}
     */
    private function mapDomaine(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [null, null];
        }

        $parts = array_map('trim', explode(',', $raw));
        foreach ($parts as $part) {
            if (isset(self::DOMAINE_MAP[$part])) {
                $enum = self::DOMAINE_MAP[$part];

                $note = count($parts) > 1
                    ? "Domaines de contribution multiples dans la source (\"{$raw}\") — seul le premier reconnu a été conservé comme champ structuré."
                    : null;

                return [$enum, $note];
            }
        }

        return [null, "Domaine de contribution non reconnu dans la source : \"{$raw}\"."];
    }

    private function normalizePhone(mixed $raw): string
    {
        if ($raw === null || $raw === '') {
            return '';
        }

        if (is_float($raw) || is_int($raw)) {
            // Le fichier Excel stocke ce champ en nombre (perte du "+"
            // éventuel, mais pas de zéro initial : aucun numéro de la
            // source ne commence par 0, vérifié à l'inspection du fichier).
            return (string) (int) round($raw);
        }

        return trim((string) $raw);
    }

    private function nullableTrim(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $trimmed = trim((string) $raw);

        return $trimmed === '' ? null : $trimmed;
    }

    private function toBool(mixed $raw): bool
    {
        if (is_bool($raw)) {
            return $raw;
        }
        if (is_numeric($raw)) {
            return (float) $raw !== 0.0;
        }

        return in_array(strtolower(trim((string) $raw)), ['true', 'vrai', '1', 'oui'], true);
    }

    private function toTimestamp(mixed $raw): int
    {
        $carbon = $this->toCarbon($raw);

        return $carbon?->getTimestamp() ?? 0;
    }

    private function toCarbon(mixed $raw): ?\Illuminate\Support\Carbon
    {
        if ($raw instanceof \DateTimeInterface) {
            return \Illuminate\Support\Carbon::instance($raw);
        }
        if (is_string($raw) && $raw !== '') {
            try {
                return \Illuminate\Support\Carbon::parse($raw);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
