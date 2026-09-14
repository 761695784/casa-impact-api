<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\MembershipReferenceCounter;
use Illuminate\Support\Facades\DB;

/**
 * Format aligné sur la numérotation OFFICIELLE historique (fichier Excel
 * pré-site) : CI-{année}-{séquence sur 4 chiffres}, ex. CI-2026-0001.
 *
 * Corrigé le 2026-09-14 (2e correctif, accord explicite) : le numéro
 * suivant n'est PLUS calculé à partir d'un compteur qui ne fait
 * qu'augmenter (ce qui laissait des "trous" après une suppression — ex.
 * supprimer le dernier membre CI-2026-0172 puis en créer un nouveau donnait
 * CI-2026-0173 au lieu de réutiliser 0172). Le prochain numéro est
 * désormais TOUJOURS recalculé, à chaque génération, à partir du plus
 * grand `numero_membre` RÉELLEMENT présent en base pour l'année — donc
 * "dynamique" comme demandé : une suppression libère bien le numéro pour
 * le prochain membre.
 *
 * La table `membership_reference_counters` (une ligne par année) ne sert
 * plus que de VERROU applicatif (lockForUpdate) pour empêcher deux
 * générations simultanées d'obtenir le même numéro — sa colonne `sequence`
 * est mise à jour à chaque génération pour rester lisible en base, mais
 * n'est plus la source de vérité (elle ne l'était plus de toute façon dès
 * qu'un membre pouvait être supprimé).
 */
class MembershipReferenceGenerator
{
    public function generate(): string
    {
        $annee = (int) date('Y');

        $sequence = DB::transaction(function () use ($annee) {
            $counter = MembershipReferenceCounter::query()
                ->where('annee', $annee)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                // Double SELECT volontaire : protège contre le cas limite
                // d'un changement d'année pile au moment d'une soumission
                // concurrente (voir ApplicationReferenceGenerator).
                $counter = MembershipReferenceCounter::create(['annee' => $annee, 'sequence' => 0]);
                $counter = MembershipReferenceCounter::query()->where('annee', $annee)->lockForUpdate()->first();
            }

            // Toujours recalculé sur l'état réel de la table — jamais une
            // simple incrémentation du compteur stocké (voir docblock de
            // classe). Le lock ci-dessus garantit qu'aucune autre requête
            // ne peut lire/écrire ce même verrou en même temps, donc pas de
            // collision possible entre deux créations concurrentes.
            $nextSequence = $this->maxSequenceFor($annee) + 1;

            $counter->update(['sequence' => $nextSequence]);

            return $nextSequence;
        });

        return sprintf('CI-%d-%04d', $annee, $sequence);
    }

    /**
     * Plus grand numéro de séquence RÉELLEMENT présent en base pour une
     * année donnée (0 si aucun membre au format CI-{année}-NNNN). Appelé
     * sous verrou par generate() ; peut aussi servir de diagnostic.
     */
    private function maxSequenceFor(int $annee): int
    {
        $max = 0;

        Membership::query()
            ->where('numero_membre', 'like', "CI-{$annee}-%")
            ->pluck('numero_membre')
            ->each(function (string $numero) use (&$max, $annee) {
                if (preg_match('/^CI-'.$annee.'-(\d+)$/', $numero, $matches)) {
                    $seq = (int) $matches[1];

                    if ($seq > $max) {
                        $max = $seq;
                    }
                }
            });

        return $max;
    }
}
