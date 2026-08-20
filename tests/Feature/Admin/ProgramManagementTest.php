<?php

use App\Models\Domain;
use App\Models\Program;
use App\Models\ProgramType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsCommunicationForPrograms(): User
{
    $user = User::factory()->create();
    $user->assignRole('communication');

    return $user;
}

it("permet à communication de créer un programme avec un slug généré automatiquement", function () {
    $user = actingAsCommunicationForPrograms();
    $domain = Domain::factory()->create();
    $type = ProgramType::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/admin/programs', [
        'titre' => 'Bootcamp Entrepreneuriat',
        'domain_id' => $domain->id,
        'program_type_id' => $type->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.slug', 'bootcamp-entrepreneuriat')
        ->assertJsonPath('data.statut', 'brouillon')
        ->assertJsonPath('data.domain.id', $domain->id)
        ->assertJsonPath('data.program_type.id', $type->id);
});

it("refuse la création d'un programme à un rôle sans permission programs.create", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');
    $domain = Domain::factory()->create();
    $type = ProgramType::factory()->create();

    $this->actingAs($user)->postJson('/api/admin/programs', [
        'titre' => 'Bootcamp',
        'domain_id' => $domain->id,
        'program_type_id' => $type->id,
    ])->assertForbidden();
});

it("rejette un domain_id ou program_type_id inexistant", function () {
    $user = actingAsCommunicationForPrograms();

    $this->actingAs($user)->postJson('/api/admin/programs', [
        'titre' => 'Bootcamp',
        'domain_id' => 9999,
        'program_type_id' => 9999,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['domain_id', 'program_type_id']);
});

it("rejette une date_fin antérieure à la date_debut", function () {
    $user = actingAsCommunicationForPrograms();
    $domain = Domain::factory()->create();
    $type = ProgramType::factory()->create();

    $this->actingAs($user)->postJson('/api/admin/programs', [
        'titre' => 'Bootcamp',
        'domain_id' => $domain->id,
        'program_type_id' => $type->id,
        'date_debut' => '2026-06-10',
        'date_fin' => '2026-06-01',
    ])->assertStatus(422)->assertJsonValidationErrors('date_fin');
});

it("permet de publier un programme via l'update standard (pas d'endpoint dédié)", function () {
    $user = actingAsCommunicationForPrograms();
    $program = Program::factory()->create(['statut' => 'brouillon']);

    $this->actingAs($user)
        ->putJson("/api/admin/programs/{$program->id}", ['statut' => 'publie'])
        ->assertOk()
        ->assertJsonPath('data.statut', 'publie');
});

it("filtre la liste des programmes par domaine", function () {
    $user = actingAsCommunicationForPrograms();
    $domainA = Domain::factory()->create();
    $domainB = Domain::factory()->create();
    Program::factory()->create(['domain_id' => $domainA->id]);
    Program::factory()->create(['domain_id' => $domainB->id]);

    $response = $this->actingAs($user)->getJson("/api/admin/programs?domain_id={$domainA->id}");

    $response->assertOk()->assertJsonCount(1, 'data');
});

it("supprime un programme", function () {
    $user = actingAsCommunicationForPrograms();
    $program = Program::factory()->create();

    $this->actingAs($user)->deleteJson("/api/admin/programs/{$program->id}")->assertOk();

    $this->assertDatabaseMissing('programs', ['id' => $program->id]);
});
