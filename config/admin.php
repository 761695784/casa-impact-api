<?php

/**
 * Isole les seuls appels à env() nécessaires pour AdminUserSeeder derrière
 * un fichier de config — jamais d'appel env() direct dans le code applicatif
 * (Seeder inclus), qui est la convention Laravel standard et, surtout, la
 * seule façon fiable de surcharger ces valeurs dans les tests : une fois
 * qu'un vrai `.env` a chargé ADMIN_SEED_EMAIL/PASSWORD dans $_ENV au
 * démarrage de l'app, un `putenv()` fait depuis un test n'a plus la
 * priorité (voir AdminUserSeederTest, corrigé le 2026-08-20). `config()`,
 * lui, se réinitialise proprement à chaque test (application fraîche par
 * test) et reste directement surchargeable avec `config(['admin...' => ...])`.
 */
return [
    'seed_email' => env('ADMIN_SEED_EMAIL'),
    'seed_password' => env('ADMIN_SEED_PASSWORD'),
    'seed_name' => env('ADMIN_SEED_NAME', 'Administrateur Principal'),
];
