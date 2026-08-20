<?php

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsCommunication(): User
{
    $user = User::factory()->create();
    $user->assignRole('communication');

    return $user;
}

it("permet à communication de créer une page avec un slug généré automatiquement", function () {
    $user = actingAsCommunication();

    $response = $this->actingAs($user)->postJson('/api/admin/pages', [
        'titre' => 'Notre vision',
        'corps' => "Faire de la Casamance un territoire de référence en Afrique.",
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.slug', 'notre-vision')
        ->assertJsonPath('data.statut', 'brouillon');
});

it("refuse la création de page à un rôle sans permission pages.create", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->postJson('/api/admin/pages', [
        'titre' => 'Notre vision',
        'corps' => 'contenu',
    ])->assertForbidden();
});

it("rejette un slug déjà utilisé par une autre page", function () {
    $user = actingAsCommunication();
    Page::factory()->create(['slug' => 'notre-mission']);

    $this->actingAs($user)->postJson('/api/admin/pages', [
        'titre' => 'Notre mission',
        'slug' => 'notre-mission',
        'corps' => 'contenu',
    ])->assertStatus(422)->assertJsonValidationErrors('slug');
});

it("permet de publier une page via l'update standard (pas d'endpoint dédié)", function () {
    $user = actingAsCommunication();
    $page = Page::factory()->create(['statut' => 'brouillon']);

    $this->actingAs($user)
        ->putJson("/api/admin/pages/{$page->id}", ['statut' => 'publie'])
        ->assertOk()
        ->assertJsonPath('data.statut', 'publie');
});

it("supprime une page", function () {
    $user = actingAsCommunication();
    $page = Page::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/admin/pages/{$page->id}")
        ->assertOk();

    $this->assertDatabaseMissing('pages', ['id' => $page->id]);
});
