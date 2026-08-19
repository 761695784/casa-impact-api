<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

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
    $user = User::factory()->create();
    $user->assignRole('administrateur-principal');

    $this->actingAs($user)
        ->postJson('/api/admin/logout')
        ->assertOk();

    $this->assertGuest();
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
    $user = User::factory()->create();
    $user->assignRole('communication');

    $response = $this->actingAs($user)->getJson('/api/admin/me');

    $response->assertOk()->assertJsonPath('permissions', []);
});
