<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Restreint l'accès à la doc API générée par Scramble (/docs/api) en dehors
 * de l'environnement `local`.
 *
 * Scramble ouvre `/docs/api` sans restriction en local, et passe par le
 * middleware `RestrictedDocsAccess` dans les autres environnements — ce
 * middleware (fourni par le package) vérifie le Gate `viewApiDocs` défini
 * ci-dessous. Voir config/scramble.php pour la configuration générale.
 *
 * Décision Casa Impact : la doc n'expose aucune donnée réelle (uniquement
 * la structure des endpoints/schemas), mais reste réservée à l'équipe —
 * tout utilisateur authentifié possédant l'un des 3 rôles fixes de
 * l'association peut la consulter (pas de restriction par email, contraire
 * à `administrateur-principal` seul, pour que l'équipe technique puisse
 * s'y référer sans solliciter un compte admin-principal à chaque fois).
 */
class ScrambleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('viewApiDocs', function ($user) {
            return $user !== null
                && $user->hasAnyRole([
                    'administrateur-principal',
                    'communication',
                    'gestionnaire-candidatures',
                ]);
        });
    }
}
