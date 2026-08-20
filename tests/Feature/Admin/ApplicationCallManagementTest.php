<?php

use App\Models\ApplicationCall;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsGestionnaireCandidatures(): User
{
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    return $user;
}

it("permet à gestionnaire-candidatures de créer un appel avec un slug généré automatiquement", function () {
    $user = actingAsGestionnaireCandidatures();
    $program = Program::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/admin/application-calls', [
        'titre' => 'Appel à candidatures Bootcamp 2026',
        'region' => 'ziguinchor',
        'program_id' => $program->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.slug', 'appel-a-candidatures-bootcamp-2026')
        ->assertJsonPath('data.statut', 'brouillon')
        ->assertJsonPath('data.region', 'ziguinchor');
});

it("refuse la création d'un appel à communication (pas de permission application-calls.create)", function () {
    $user = User::factory()->create();
    $user->assignRole('communication');
    $program = Program::factory()->create();

    $this->actingAs($user)->postJson('/api/admin/application-calls', [
        'titre' => 'Appel',
        'region' => 'kolda',
        'program_id' => $program->id,
    ])->assertForbidden();
});

it("rejette une région hors de la liste fixe", function () {
    $user = actingAsGestionnaireCandidatures();
    $program = Program::factory()->create();

    $this->actingAs($user)->postJson('/api/admin/application-calls', [
        'titre' => 'Appel',
        'region' => 'dakar',
        'program_id' => $program->id,
    ])->assertStatus(422)->assertJsonValidationErrors('region');
});

it("rejette un program_id inexistant", function () {
    $user = actingAsGestionnaireCandidatures();

    $this->actingAs($user)->postJson('/api/admin/application-calls', [
        'titre' => 'Appel',
        'region' => 'sedhiou',
        'program_id' => 9999,
    ])->assertStatus(422)->assertJsonValidationErrors('program_id');
});

it("rejette une date_fin antérieure à la date_debut", function () {
    $user = actingAsGestionnaireCandidatures();
    $program = Program::factory()->create();

    $this->actingAs($user)->postJson('/api/admin/application-calls', [
        'titre' => 'Appel',
        'region' => 'kolda',
        'program_id' => $program->id,
        'date_debut' => '2026-06-10',
        'date_fin' => '2026-06-01',
    ])->assertStatus(422)->assertJsonValidationErrors('date_fin');
});

it("permet de publier un appel via l'update standard (pas d'endpoint dédié)", function () {
    $user = actingAsGestionnaireCandidatures();
    $applicationCall = ApplicationCall::factory()->create(['statut' => 'brouillon']);

    $this->actingAs($user)
        ->putJson("/api/admin/application-calls/{$applicationCall->id}", ['statut' => 'publie'])
        ->assertOk()
        ->assertJsonPath('data.statut', 'publie');
});

it("filtre la liste des appels par région", function () {
    $user = actingAsGestionnaireCandidatures();
    ApplicationCall::factory()->create(['region' => 'ziguinchor']);
    ApplicationCall::factory()->create(['region' => 'kolda']);

    $response = $this->actingAs($user)->getJson('/api/admin/application-calls?region=ziguinchor');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it("supprime un appel à candidatures", function () {
    $user = actingAsGestionnaireCandidatures();
    $applicationCall = ApplicationCall::factory()->create();

    $this->actingAs($user)->deleteJson("/api/admin/application-calls/{$applicationCall->id}")->assertOk();

    $this->assertDatabaseMissing('application_calls', ['id' => $applicationCall->id]);
});
