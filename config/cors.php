<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    // Domaines du site Next.js autorisés à appeler l'API avec des cookies.
    // On autorise les deux variantes (avec et sans www) pour éviter les blocages
    // CORS selon la façon dont le visiteur tape l'adresse.
    // En local, ajoute aussi http://localhost:3000 pour tes tests.
    'allowed_origins' => [
        'https://casaimpact.org',
        'https://www.casaimpact.org',
        'http://localhost:3000',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    // OBLIGATOIRE pour Sanctum SPA (cookies) : doit être à true, jamais '*' dans allowed_origins ci-dessus.
    'supports_credentials' => true,
];
