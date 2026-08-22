<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Liaison explicite à TestCase + RefreshDatabase dans CHAQUE fichier, sans
// dépendre du contenu de tests/Pest.php (qui peut varier selon comment
// `pest:install` a été exécuté) — évite l'erreur "undefined method seed()"
// causée par un fichier lié uniquement au trait RefreshDatabase sans base
// TestCase (donc sans $this->seed/$this->artisan/$this->postJson...).
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Sanctum ne démarre la session (StartSession, CSRF...) que pour les
    // requêtes qu'il reconnaît comme venant d'un frontend "stateful" —
    // EnsureFrontendRequestsAreStateful::fromFrontend() exige un header
    // Referer/Origin ET une correspondance avec config('sanctum.stateful').
    // Plutôt que de dépendre de la valeur exacte de SANCTUM_STATEFUL_DOMAINS
    // dans le .env de la machine qui fait tourner les tests (fragile, source
    // du "Session store not set on request." observé), on force ici un
    // wildcard : n'importe quel domaine sera reconnu comme frontend pour la
    // durée des tests. Un header Referer/Origin reste nécessaire (sans lui,
    // fromFrontend() retourne false avant même de regarder la config).
    config(['sanctum.stateful' => ['*']]);
    $this->withHeader('Referer', config('app.url'));
});

it("connecte un administrateur avec des identifiants valides", function () {
    $user = User::factory()->create(['password' => bcrypt('Password123!')]);
    $user->assignRole('administrateur-principal');

    $response = $this->postJson('/api/admin/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $response->assertOk()->assertJsonPath('data.email', $user->email);
    $this->assertAuthenticatedAs($user);
});

it("refuse une connexion avec un mauvais mot de passe (422, pas 401)", function () {
    $user = User::factory()->create(['password' => bcrypt('Password123!')]);
    $user->assignRole('administrateur-principal');

    $response = $this->postJson('/api/admin/login', [
        'email' => $user->email,
        'password' => 'mauvais-mot-de-passe',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
    $this->assertGuest();
});

it("déconnecte un administrateur authentifié", function () {
    // Cause réelle trouvée le 2026-08-22 (après 2 essais infructueux) :
    // `/api/admin/logout` est protégée par `auth:sanctum`, donc CETTE
    // requête déclenche déjà une résolution du guard Sanctum, qui mémoïse
    // (via `once()`) l'utilisateur résolu sur l'objet guard — mémoïsation
    // qui reste "collée" pour tout le reste du test (le conteneur Laravel
    // n'est PAS recréé entre deux appels HTTP consécutifs dans un même
    // test, seulement entre deux tests). Résultat : une 3e requête réelle
    // vers une route Sanctum-protégée (ex. /api/admin/me) rejouait ce
    // résultat mémoïsé et semblait "encore connectée", MÊME SI
    // AuthController::logout() avait bien vidé le guard `web` — ce n'était
    // donc jamais un bug de logout() lui-même, mais un artefact du modèle
    // de test de Laravel (aucun rapport avec un vrai navigateur, où chaque
    // requête HTTP est un process/état réellement neuf).
    //
    // Fix définitif : vérifier l'état du guard `web` DIRECTEMENT (celui
    // que logout() manipule explicitement) juste après l'appel logout, sans
    // déclencher de 3e requête HTTP vers une route protégée — on évite ainsi
    // complètement l'artefact de mémoïsation Sanctum décrit ci-dessus.
    // `actingAs($user, 'web')` (guard explicite, pas le guard par défaut,
    // potentiellement 'sanctum') + `assertGuest('web')` (même guard explicite)
    // garantissent qu'on observe exactement ce que logout() modifie.
    $user = User::factory()->create();
    $user->assignRole('administrateur-principal');

    $this->actingAs($user, 'web');

    $this->postJson('/api/admin/logout')->assertOk();

    $this->assertGuest('web');
});

it("refuse l'accès aux routes protégées sans authentification", function () {
    $this->getJson('/api/admin/me')->assertUnauthorized();
});

it("retourne la liste complète des permissions pour administrateur-principal via /me", function () {
    $user = User::factory()->create();
    $user->assignRole('administrateur-principal');

    $response = $this->actingAs($user)->getJson('/api/admin/me');

    $response->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('permissions', fn ($permissions) => in_array('users.view', $permissions)
            && in_array('roles.view', $permissions));
});

it("ne retourne que les permissions explicitement assignées pour un rôle non-principal", function () {
    // Ce test vérifiait à l'origine un tableau vide, car `communication`
    // n'avait encore aucune permission assignée (Module 1). Depuis le
    // Module 2, `RolesAndPermissionsSeeder` donne à `communication` les
    // permissions `pages.*`/`domains.*` — c'est le comportement voulu.
    // On compare donc dynamiquement à ce que le rôle possède réellement
    // plutôt qu'à une liste figée qui serait redevenue obsolète à chaque
    // futur module ajoutant des permissions à ce rôle.
    $user = User::factory()->create();
    $user->assignRole('communication');

    $response = $this->actingAs($user)->getJson('/api/admin/me');

    $response->assertOk()
        ->assertJsonPath('permissions', $user->getAllPermissions()->pluck('name')->values()->all());
});
