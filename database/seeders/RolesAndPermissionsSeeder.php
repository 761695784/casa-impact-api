<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder incrémental (décision validée le 2026-08-19) : on ne seed ici que
 * les permissions utiles au module Administration. Chaque nouveau module
 * (Programmes, Appels à candidatures, Actualités...) ajoutera ses propres
 * permissions à ce fichier au moment de son implémentation, plutôt que de
 * pré-inventer l'ensemble maintenant.
 *
 * Rôles fixes et lecture seule côté API (voir RoleController) : toute
 * évolution de la liste des rôles passe par ce seeder, versionné avec le
 * code, jamais par une création dynamique via l'API.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $administrateurPrincipal = Role::findOrCreate('administrateur-principal', 'web');
        $administrateurPrincipal->syncPermissions($permissions);

        // Ces deux rôles n'ont aucune permission d'Administration à ce stade
        // (principe "éviter les permissions excessives", §27 du brief) — ils
        // recevront leurs permissions propres dans les seeders des modules
        // qui les concernent (Actualités pour `communication`, Candidatures
        // pour `gestionnaire-candidatures`).
        Role::findOrCreate('communication', 'web');
        Role::findOrCreate('gestionnaire-candidatures', 'web');
    }
}
