<?php

use App\Models\ProgramType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsCommunicationForTypes(): User
{
    $user = User::factory()->create();
    $user->assignRole('communication');

    return $user;
}

it("permet à communication de créer un type de programme avec un slug généré automatiquement", function () {
    $user = actingAsCommunicationForTypes();

    $response = $this->actingAs($user)->postJson('/api/admin/program-types', [
        'nom' => 'Formation professionnelle',
    ]);

    $response->assertCreated()->assertJsonPath('data.slug', 'formation-professionnelle');
});

it("refuse la création d'un type de programme à un rôle sans permission", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->postJson('/api/admin/program-types', ['nom' => 'Masterclass'])
        ->assertForbidden();
});

it("rejette un slug déjà utilisé par un autre type de programme", function () {
    $user = actingAsCommunicationForTypes();
    ProgramType::factory()->create(['slug' => 'caravane']);

    $this->actingAs($user)->postJson('/api/admin/program-types', [
        'nom' => 'Caravane',
        'slug' => 'caravane',
    ])->assertStatus(422)->assertJsonValidationErrors('slug');
});

it("met à jour un type de programme existant", function () {
    $user = actingAsCommunicationForTypes();
    $type = ProgramType::factory()->create(['nom' => 'Incubation']);

    $this->actingAs($user)->putJson("/api/admin/program-types/{$type->id}", [
        'description' => 'Accompagnement des porteurs de projet.',
    ])->assertOk()->assertJsonPath('data.description', 'Accompagnement des porteurs de projet.');
});

it("supprime un type de programme non utilisé", function () {
    $user = actingAsCommunicationForTypes();
    $type = ProgramType::factory()->create();

    $this->actingAs($user)->deleteJson("/api/admin/program-types/{$type->id}")->assertOk();

    $this->assertDatabaseMissing('program_types', ['id' => $type->id]);
});

it("refuse (409) la suppression d'un type de programme encore utilisé par un programme", function () {
    $user = actingAsCommunicationForTypes();
    $type = ProgramType::factory()->create();
    \App\Models\Program::factory()->create(['program_type_id' => $type->id]);

    $this->actingAs($user)->deleteJson("/api/admin/program-types/{$type->id}")
        ->assertStatus(409);

    $this->assertDatabaseHas('program_types', ['id' => $type->id]);
});
