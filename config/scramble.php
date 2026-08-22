<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

return [

    /*
     * Toutes les routes commençant par `api` sont documentées — Admin ET
     * Public (voir routes/api.php, aucun préfixe de version, conformément à
     * architecturev1.md §F, décision #8). C'est le comportement par défaut
     * de Scramble, pas besoin de le restreindre davantage.
     */
    'api_path' => 'api',

    'api_domain' => null,

    'export_path' => 'api.json',

    'cache' => [
        'key' => 'scramble.openapi',
        'store' => 'file',
    ],

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),

        'description' => <<<'MD'
API REST de Casa Impact — plateforme de gestion des programmes, appels à
candidatures, candidatures, actualités, talents, témoignages, partenaires,
indicateurs d'impact et messages de contact de l'association (région de
Casamance, Sénégal).

## Authentification (routes `/api/admin/*`)

L'authentification admin utilise **Laravel Sanctum en mode SPA (cookie de
session)** — PAS de jeton Bearer. Flux attendu par un client (SPA / mobile) :

1. `GET /sanctum/csrf-cookie` — obtient le cookie `XSRF-TOKEN`.
2. `POST /api/admin/login` (email + mot de passe) — ouvre la session,
   dépose le cookie de session Laravel.
3. Chaque requête suivante vers `/api/admin/*` doit envoyer ce cookie de
   session ET l'en-tête `X-XSRF-TOKEN` (valeur du cookie `XSRF-TOKEN`) pour
   toute méthode non-GET (protection CSRF standard de Sanctum SPA).
4. `POST /api/admin/logout` ferme la session.

Le cadenas "cookieAuth" affiché sur les endpoints ci-dessous représente ce
mécanisme (et non un jeton Bearer, bien que les deux apparaissent parfois
similaires dans un outil générique de doc OpenAPI).

Les routes `/api/public/*` ne nécessitent aucune authentification.

## Rôles

Trois rôles fixes (spatie/laravel-permission) : `administrateur-principal`,
`communication`, `gestionnaire-candidatures`. Voir
`database/seeders/RolesAndPermissionsSeeder.php` pour la table complète
rôle → permissions.
MD,
    ],

    'ui' => [
        'title' => 'Casa Impact — Documentation API',
    ],

    'dev_tools' => [
        'enabled' => env('SCRAMBLE_DEV_TOOLS', env('APP_DEBUG', false)),
    ],

    'renderer' => 'elements',

    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            // 'include' est indispensable ici : sans lui, le bouton "Try it"
            // de la doc n'enverrait pas le cookie de session Sanctum et
            // toutes les requêtes admin échoueraient en 401 depuis l'UI.
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],

        'scalar' => [
            'view' => 'scramble::scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',
            'theme' => 'laravel',
            'proxyUrl' => 'https://proxy.scalar.com',
            'darkMode' => false,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
        ],
    ],

    /*
     * IMPORTANT : quand `api_path` est une chaîne statique sans wildcard
     * (notre cas, 'api'), Scramble retire le préfixe `/api` de chaque
     * chemin documenté (ex. `/admin/login` au lieu de `/api/admin/login`)
     * et s'attend à ce que ce soit le SERVEUR qui porte ce préfixe. Une
     * entrée `servers` personnalisée doit donc explicitement se terminer
     * par `/api` — sinon le bouton "Try it" appelle une URL sans `/api` et
     * échoue en 404 (bug corrigé le 2026-08-22, la version précédente de ce
     * fichier oubliait le `/api`).
     */
    'servers' => [
        'Local' => 'http://localhost:8000/api',
        'Production' => rtrim(env('APP_URL', 'https://api.casaimpact.org'), '/').'/api',
    ],

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    'flatten_deep_query_parameters' => true,

    /*
     * Comportement par défaut de Scramble : la doc est ouverte en `local`,
     * et passe par `RestrictedDocsAccess` (donc par le Gate `viewApiDocs`,
     * voir App\Providers\ScrambleServiceProvider) dans les autres
     * environnements. On ne touche pas à ce comportement par défaut.
     */
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    /*
     * Les routes admin sont protégées par `auth:sanctum` → Scramble détecte
     * ce middleware et marque automatiquement ces endpoints comme
     * nécessitant une authentification. Le schéma par défaut de Scramble
     * pour `auth:*` est un Bearer token — on le remplace ici par un schéma
     * `apiKey` en cookie ("cookieAuth"), qui reflète fidèlement notre
     * authentification Sanctum SPA (voir info.description ci-dessus pour le
     * flux complet).
     */
    'security_strategy' => [
        MiddlewareAuthSecurityStrategy::class,
        [
            'middleware' => ['auth', 'auth:*'],
            'scheme' => SecurityScheme::apiKey('cookie', 'laravel_session'),
        ],
    ],

];
