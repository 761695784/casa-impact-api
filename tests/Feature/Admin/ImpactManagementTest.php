<?php

use App\Models\ImpactIndicator;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsCommunicationForImpact(): User
{
    $user = User::factory()->create();
    $user->assignRole('communication');

    return $user;
}

it("permet à communication de créer un indicateur d'impact", function () {
    $user = actingAsCommunicationForImpact();

    $response = $this->actingAs($user)->postJson('/api/admin/impact-indicators', [
        'libelle' => 'Jeunes formés',
        'unite' => 'personnes',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.libelle', 'Jeunes formés')
        ->assertJsonPath('data.unite', 'personnes');
});

it("refuse la création d'un indicateur sans libellé", function () {
    $user = actingAsCommunicationForImpact();

    $this->actingAs($user)->postJson('/api/admin/impact-indicators', [
        'unite' => 'personnes',
    ])->assertStatus(422)->assertJsonValidationErrors('libelle');
});

it("refuse la création à un rôle sans permission impact.create", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->postJson('/api/admin/impact-indicators', [
        'libelle' => 'Jeunes formés',
    ])->assertForbidden();
});

it('permet d’ajouter une valeur à un indicateur existant', function () {
    $user = actingAsCommunicationForImpact();
    $indicator = ImpactIndicator::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/admin/impact-indicators/{$indicator->id}/values", [
        'valeur' => 120.5,
        'periode' => '2026',
        'region' => 'ziguinchor',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.valeur', 120.5)
        ->assertJsonPath('data.periode', '2026');

    $this->assertDatabaseHas('impact_values', [
        'impact_indicator_id' => $indicator->id,
        'periode' => '2026',
    ]);
});

it('supprime un indicateur et ses valeurs en cascade', function () {
    $user = actingAsCommunicationForImpact();
    $indicator = ImpactIndicator::factory()->create();
    $indicator->values()->create(['valeur' => 10, 'periode' => '2025']);

    $this->actingAs($user)->deleteJson("/api/admin/impact-indicators/{$indicator->id}")->assertOk();

    $this->assertDatabaseMissing('impact_indicators', ['id' => $indicator->id]);
    $this->assertDatabaseMissing('impact_values', ['impact_indicator_id' => $indicator->id]);
});
