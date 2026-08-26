<?php

namespace App\Services;

use App\Models\MembershipReferenceCounter;
use Illuminate\Support\Facades\DB;

/**
 * Réplique EXACTEMENT le mécanisme de App\Services\ApplicationReferenceGenerator
 * (même format de sortie CI-{année}-{séquence sur 6 chiffres}, même garantie
 * d'atomicité via lockForUpdate), mais sur sa propre table de compteur
 * (`membership_reference_counters`) — compteur indépendant de celui des
 * candidatures, conformément à la demande explicite du 2026-08-24.
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
                // Voir ApplicationReferenceGenerator pour le raisonnement
                // complet sur ce double SELECT (protection contre le cas
                // limite d'un changement d'année pile au moment d'une
                // soumission concurrente).
                $counter = MembershipReferenceCounter::create(['annee' => $annee, 'sequence' => 0]);
                $counter = MembershipReferenceCounter::query()->where('annee', $annee)->lockForUpdate()->first();
            }

            $counter->increment('sequence');

            return $counter->sequence;
        });

        return sprintf('CI-%d-%06d', $annee, $sequence);
    }
}
