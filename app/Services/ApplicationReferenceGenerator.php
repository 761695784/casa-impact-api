<?php

namespace App\Services;

use App\Models\ApplicationReferenceCounter;
use Illuminate\Support\Facades\DB;

/**
 * Génère une référence de candidature unique, format CI-{année}-{séquence
 * sur 6 chiffres} (ex. CI-2026-000123), SANS collision possible sous
 * soumissions concurrentes (architecturev1.md §I : "l'atomicité de la
 * génération n'est pas négociable").
 *
 * Approche retenue : une ligne compteur PAR ANNÉE dans
 * `application_reference_counters`, verrouillée (`lockForUpdate`) et
 * incrémentée à l'intérieur d'une transaction dédiée — plus fiable qu'un
 * `Application::count()+1` (deux requêtes concurrentes pourraient lire le
 * même count avant que l'une des deux n'insère).
 */
class ApplicationReferenceGenerator
{
    public function generate(): string
    {
        $annee = (int) date('Y');

        $sequence = DB::transaction(function () use ($annee) {
            $counter = ApplicationReferenceCounter::query()
                ->where('annee', $annee)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                // La contrainte unique sur `annee` protège contre une
                // création en double si deux transactions arrivent ici en
                // même temps sur une nouvelle année : la seconde échouera
                // sur l'unicité et pourra être retentée par l'appelant (cas
                // limite extrêmement rare — un changement d'année pile au
                // moment d'une soumission).
                $counter = ApplicationReferenceCounter::create(['annee' => $annee, 'sequence' => 0]);
                $counter = ApplicationReferenceCounter::query()->where('annee', $annee)->lockForUpdate()->first();
            }

            $counter->increment('sequence');

            return $counter->sequence;
        });

        return sprintf('CI-%d-%06d', $annee, $sequence);
    }
}
