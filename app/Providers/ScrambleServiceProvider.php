<?php

namespace App\Providers;

use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

/**
 * Restreint l'accès à la doc API générée par Scramble (/docs/api) en dehors
 * de l'environnement `local`, et déclare la stratégie de sécurité OpenAPI.
 *
 * Scramble ouvre `/docs/api` sans restriction en local, et passe par le
 * middleware `RestrictedDocsAccess` dans les autres environnements — ce
 * middleware (fourni par le package) vérifie le Gate `viewApiDocs` défini
 * ci-dessous. Voir config/scramble.php pour la configuration générale.
 *
 * Décision Casa Impact : la doc n'expose aucune donnée réelle (uniquement
 * la structure des endpoints/schemas), mais reste réservée à l'équipe —
 * tout utilisateur authentifié possédant l'un des 3 rôles fixes de
 * l'association peut la consulter.
 */
class ScrambleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         * `php artisan config:cache` sérialise la configuration avec
         * var_export(), qui ne sait pas reconstruire un objet dépourvu de
         * __set_state(). Et cette commande boote l'application AVANT de
         * sérialiser : déclarer le schéma ici sans précaution la ferait
         * échouer tout autant que de le déclarer dans config/scramble.php.
         *
         * On ne pose donc la stratégie que là où Scramble en a besoin : sur
         * une requête HTTP (rendu de /docs/api), pendant les tests, et pour
         * ses propres commandes `scramble:*`. Les autres commandes Artisan —
         * `config:cache` et `optimize` en tête — voient une configuration
         * entièrement sérialisable.
         *
         * Les routes admin sont protégées par `auth:sanctum` → Scramble
         * détecte ce middleware et marque ces endpoints comme nécessitant
         * une authentification. Son schéma par défaut pour `auth:*` est un
         * Bearer token — on le remplace par un schéma `apiKey` en cookie
         * ("cookieAuth"), fidèle à notre authentification Sanctum SPA.
         */
        $command = $_SERVER['argv'][1] ?? '';

        if ($this->app->runningInConsole()
            && ! $this->app->runningUnitTests()
            && ! str_starts_with($command, 'scramble:')) {
            return;
        }

        Config::set('scramble.security_strategy', [
            MiddlewareAuthSecurityStrategy::class,
            [
                'middleware' => ['auth', 'auth:*'],
                'scheme' => SecurityScheme::apiKey('cookie', 'laravel_session'),
            ],
        ]);
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
