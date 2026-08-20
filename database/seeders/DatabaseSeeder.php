<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DomainsSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Aucun utilisateur ni donnée métier de démonstration : conforme à
        // la règle "aucune donnée fictive" (brief §4.6 / architecturev1.md).
        // Le premier compte admin se crée via `php artisan admin:create`.
        // Les 6 domaines sont une exception explicite : ce sont des noms
        // officiels du brief, pas des données inventées.
    }
}
