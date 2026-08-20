<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder incrémental : chaque module ajoute ses propres permissions à ce
 * fichier au moment de son implémentation.
 * Module 1 (Administration) : users.*, roles.view.
 * Module 2 (Pages/Domaines) : pages.*, domains.*.
 * Module 3 (Programmes) : programs.*, program-types.*.
 * Module 4 (Appels à candidatures) : application-calls.*.
 * Module 5 (Candidatures) : applications.view/update/delete.
 * Module 6 (Actualités) : news.* — ajoutées ci-dessous, assignées à
 * `communication` (contenu éditorial, même logique que Pages/Programmes).
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
            'news.view',
            'news.create',
            'news.update',
            'news.delete',
        ];

        $candidaturePermissions = [
            'application-calls.view',
            'application-calls.create',
            'application-calls.update',
            'application-calls.delete',
            'applications.view',
            'applications.update',
            'applications.delete',
        ];

        foreach ([...$administrationPermissions, ...$contentPermissions, ...$candidaturePermissions] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // administrateur-principal : toutes les permissions existantes,
        // module après module (le bypass Gate::before le rend redondant
        // pour l'autorisation elle-même, mais /me doit pouvoir en afficher
        // la liste complète — voir AuthController::me()).
        $administrateurPrincipal = Role::findOrCreate('administrateur-principal', 'web');
        $administrateurPrincipal->syncPermissions([...$administrationPermissions, ...$contentPermissions, ...$candidaturePermissions]);

        // communication : gère les contenus (Pages, Domaines, Programmes,
        // Actualités, et plus tard Talents/Médiathèque) — aucune permission
        // d'Administration (users/roles) ni de Candidatures.
        $communication = Role::findOrCreate('communication', 'web');
        $communication->syncPermissions($contentPermissions);

        // gestionnaire-candidatures : gère les appels à candidatures ET les
        // candidatures elles-mêmes — aucune permission de contenu éditorial.
        $gestionnaireCandidatures = Role::findOrCreate('gestionnaire-candidatures', 'web');
        $gestionnaireCandidatures->syncPermissions($candidaturePermissions);
    }
}
