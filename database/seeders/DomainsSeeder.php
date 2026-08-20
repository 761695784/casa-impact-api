<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Les 6 domaines d'intervention proviennent du brief métier officiel
 * (section "Nos domaines"). Ce sont les seuls noms sourcés du brief — aucune
 * description n'est inventée : `description` reste null, à remplir par la
 * cellule communication depuis l'admin (règle "aucune donnée fictive").
 *
 * `updateOrCreate` sur le slug : ce seeder est idempotent, rejouable sans
 * dupliquer les 6 domaines si on relance `db:seed`.
 */
class DomainsSeeder extends Seeder
{
    public function run(): void
    {
        $domaines = [
            'Jeunesse & Leadership',
            'Entrepreneuriat & Innovation',
            'Culture & Patrimoine',
            'Sport & Talents',
            'Tourisme & Attractivité',
            'Investissement & Diaspora',
        ];

        foreach ($domaines as $ordre => $nom) {
            Domain::query()->updateOrCreate(
                ['slug' => Str::slug($nom)],
                ['nom' => $nom, 'ordre' => $ordre + 1, 'statut' => 'actif']
            );
        }
    }
}
