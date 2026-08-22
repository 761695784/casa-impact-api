<?php

use App\Models\Talent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsCommunicationForTalents(): User
{
    $user = User::factory()->create();
    $user->assignRole('communication');

    return $user;
}

it("permet à communication de créer un talent avec un slug généré automatiquement", function () {
    $user = actingAsCommunicationForTalents();

    $response = $this->actingAs($user)->postJson('/api/admin/talents', [
        'nom' => 'Aïssatou Diatta',
        'region' => 'ziguinchor',
        'presentation' => 'Entrepreneure en couture solidaire.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.slug', 'aissatou-diatta')
        ->assertJsonPath('data.statut', 'brouillon')
        ->assertJsonPath('data.region', 'ziguinchor');
});

it("refuse la création d'un talent à un rôle sans permission talents.create", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->postJson('/api/admin/talents', [
        'nom' => 'Moussa Sané',
    ])->assertForbidden();
});

it("rejette la création d'un talent sans nom", function () {
    $user = actingAsCommunicationForTalents();

    $this->actingAs($user)->postJson('/api/admin/talents', [
        'region' => 'kolda',
    ])->assertStatus(422)->assertJsonValidationErrors('nom');
});

it("rejette une région hors de la liste fixe", function () {
    $user = actingAsCommunicationForTalents();

    $this->actingAs($user)->postJson('/api/admin/talents', [
        'nom' => 'Fatou Sagna',
        'region' => 'dakar',
    ])->assertStatus(422)->assertJsonValidationErrors('region');
});

it("permet de publier un talent via l'update standard (pas d'endpoint dédié)", function () {
    $user = actingAsCommunicationForTalents();
    $talent = Talent::factory()->create(['statut' => 'brouillon']);

    $this->actingAs($user)
        ->putJson("/api/admin/talents/{$talent->id}", ['statut' => 'publie'])
        ->assertOk()
        ->assertJsonPath('data.statut', 'publie');
});

it("permet à communication d'exporter les talents en CSV", function () {
    $user = actingAsCommunicationForTalents();
    Talent::factory()->count(3)->create();

    $response = $this->actingAs($user)->get('/api/admin/talents/export');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it("refuse l'export des talents à un rôle sans permission talents.view", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->get('/api/admin/talents/export')->assertForbidden();
});

it("supprime un talent", function () {
    $user = actingAsCommunicationForTalents();
    $talent = Talent::factory()->create();

    $this->actingAs($user)->deleteJson("/api/admin/talents/{$talent->id}")->assertOk();

    $this->assertDatabaseMissing('talents', ['id' => $talent->id]);
});
