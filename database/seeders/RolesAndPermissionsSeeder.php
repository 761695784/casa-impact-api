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
 * Module 6 (Actualités) : news.*.
 * Module 9 (Talents) : talents.*.
 * Module 10 (Témoignages) : testimonials.*.
 * Module 11 (Partenaires) : partners.*.
 * Module 12 (Médiathèque) : media.manage — permission transversale, non
 *   liée à un seul modèle (voir MediaController, autorisation par chaîne
 *   de permission brute via le Gate auto-enregistré par spatie/laravel-permission).
 * Module 13 (Impact) : impact.*.
 * Module 15 (Cartographie) : locations.manage — transversale, même
 *   mécanisme que media.manage.
 * Module 16 (Contact) : contact-messages.view/update/delete (pas de
 *   .create : la création est publique et non protégée par policy).
 * Module 14 (Dashboard) : dashboard.view.
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
            'talents.view',
            'talents.create',
            'talents.update',
            'talents.delete',
            'testimonials.view',
            'testimonials.create',
            'testimonials.update',
            'testimonials.delete',
            'partners.view',
            'partners.create',
            'partners.update',
            'partners.delete',
            'impact.view',
            'impact.create',
            'impact.update',
            'impact.delete',
            'contact-messages.view',
            'contact-messages.update',
            'contact-messages.delete',
            // Transversales (Médiathèque/Cartographie) : ne sont pas liées à
            // un seul modèle de contenu, mais restent du ressort de
            // `communication` au même titre que le reste du contenu éditorial.
            'media.manage',
            'locations.manage',
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

        // Dashboard : compteurs en lecture seule, utile aux trois rôles
        // (chacun voit l'ensemble des chiffres, l'autorisation fine reste
        // au niveau de chaque module pour les actions elles-mêmes).
        $dashboardPermissions = [
            'dashboard.view',
        ];

        $allPermissions = [
            ...$administrationPermissions,
            ...$contentPermissions,
            ...$candidaturePermissions,
            ...$dashboardPermissions,
        ];

        foreach ($allPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // administrateur-principal : toutes les permissions existantes,
        // module après module (le bypass Gate::before le rend redondant
        // pour l'autorisation elle-même, mais /me doit pouvoir en afficher
        // la liste complète — voir AuthController::me()).
        $administrateurPrincipal = Role::findOrCreate('administrateur-principal', 'web');
        $administrateurPrincipal->syncPermissions($allPermissions);

        // communication : gère les contenus (Pages, Domaines, Programmes,
        // Actualités, Talents, Témoignages, Partenaires, Impact, Contact,
        // Médiathèque, Cartographie) + dashboard — aucune permission
        // d'Administration (users/roles) ni de Candidatures.
        $communication = Role::findOrCreate('communication', 'web');
        $communication->syncPermissions([...$contentPermissions, ...$dashboardPermissions]);

        // gestionnaire-candidatures : gère les appels à candidatures ET les
        // candidatures elles-mêmes + dashboard — aucune permission de
        // contenu éditorial.
        $gestionnaireCandidatures = Role::findOrCreate('gestionnaire-candidatures', 'web');
        $gestionnaireCandidatures->syncPermissions([...$candidaturePermissions, ...$dashboardPermissions]);
    }
}
