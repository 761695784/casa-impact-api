<?php

use App\Models\News;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('renvoie les statistiques agrégées à un administrateur authentifié', function () {
    $user = User::factory()->create();
    $user->assignRole('administrateur-principal');

    Program::factory()->count(2)->create(['statut' => 'publie']);
    News::factory()->count(3)->create(['statut' => 'brouillon']);

    $response = $this->actingAs($user)->getJson('/api/admin/dashboard');

    $response->assertOk()
        ->assertJsonPath('data.programmes.total', 2)
        ->assertJsonPath('data.programmes.publies', 2)
        ->assertJsonPath('data.actualites.total', 3)
        ->assertJsonPath('data.actualites.publiees', 0);
});

it('refuse l’accès au dashboard sans authentification', function () {
    $this->getJson('/api/admin/dashboard')->assertUnauthorized();
});
