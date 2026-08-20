<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder incrémental (décision validée le 2026-08-19) : chaque module ajoute
 * ses propres permissions à ce fichier au moment de son implémentation.
 * Module 1 (Administration) : users.*, roles.view.
 * Module 2 (Pages/Domaines) : pages.*, domains.*.
 * Module 3 (Programmes) : programs.*, program-types.* — ajoutées ci-dessous.
 *
 * Rôles fixes et lecture seule côté API : toute évolution de la liste des
 * rôles passe par ce seeder, versionné avec le code, jamais par une
 * création dynamique via l'API.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $administrationPermissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
        ];

        $contentPermissions = [
            'pages.view',
            'pages.create',
            'pages.update',
            'pages.delete',
            'domains.view',
            'domains.update',
            'programs.view',
            'programs.create',
            'programs.update',
            'programs.delete',
            'program-types.view',
            'program-types.create',
            'program-types.update',
            'program-types.delete',
        ];

        foreach ([...$administrationPermissions, ...$contentPermissions] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // administrateur-principal : toutes les permissions existantes,
        // module après module (le bypass Gate::before le rend redondant
        // pour l'autorisation elle-même, mais /me doit pouvoir en afficher
        // la liste complète — voir AuthController::me()).
        $administrateurPrincipal = Role::findOrCreate('administrateur-principal', 'web');
        $administrateurPrincipal->syncPermissions([...$administrationPermissions, ...$contentPermissions]);

        // communication : gère les contenus (Pages, Domaines, Programmes, et
        // plus tard Actualités/Talents/Médiathèque) — aucune permission
        // d'Administration (users/roles).
        $communication = Role::findOrCreate('communication', 'web');
        $communication->syncPermissions($contentPermissions);

        // gestionnaire-candidatures : aucune permission de contenu à ce
        // stade — recevra les siennes au module Appels à candidatures.
        Role::findOrCreate('gestionnaire-candidatures', 'web');
    }
}
