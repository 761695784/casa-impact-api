<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsAdministrateurPrincipal(): User
{
    $user = User::factory()->create();
    $user->assignRole('administrateur-principal');

    return $user;
}

it("permet à administrateur-principal de lister les utilisateurs", function () {
    $admin = actingAsAdministrateurPrincipal();
    User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonCount(4, 'data'); // 3 + admin lui-même
});

it("refuse la liste des utilisateurs à un rôle sans permission users.view", function () {
    $communication = User::factory()->create();
    $communication->assignRole('communication');

    $this->actingAs($communication)
        ->getJson('/api/admin/users')
        ->assertForbidden();
});

it("permet à administrateur-principal de créer un utilisateur avec un rôle valide", function () {
    $admin = actingAsAdministrateurPrincipal();

    $response = $this->actingAs($admin)->postJson('/api/admin/users', [
        'name' => 'Fatou Ndiaye',
        'email' => 'fatou@casaimpact.org',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'roles' => ['communication'],
    ]);

    $response->assertCreated()->assertJsonPath('data.email', 'fatou@casaimpact.org');
    $this->assertDatabaseHas('users', ['email' => 'fatou@casaimpact.org']);
});

it("rejette un rôle qui n'existe pas dans la liste fixe", function () {
    $admin = actingAsAdministrateurPrincipal();

    $this->actingAs($admin)->postJson('/api/admin/users', [
        'name' => 'Test',
        'email' => 'test@casaimpact.org',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'roles' => ['super-admin-invente'],
    ])->assertStatus(422)->assertJsonValidationErrors('roles.0');
});

it("interdit à un utilisateur de modifier son propre rôle", function () {
    $admin = actingAsAdministrateurPrincipal();

    $this->actingAs($admin)->putJson("/api/admin/users/{$admin->id}", [
        'roles' => ['communication'],
    ])->assertStatus(422)->assertJsonValidationErrors('roles');

    expect($admin->fresh()->hasRole('administrateur-principal'))->toBeTrue();
});

it("interdit à un utilisateur de supprimer son propre compte", function () {
    $admin = actingAsAdministrateurPrincipal();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$admin->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
});

it("supprime (soft delete) un autre utilisateur", function () {
    $admin = actingAsAdministrateurPrincipal();
    $target = User::factory()->create();
    $target->assignRole('communication');

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$target->id}")
        ->assertOk();

    $this->assertSoftDeleted('users', ['id' => $target->id]);
});
