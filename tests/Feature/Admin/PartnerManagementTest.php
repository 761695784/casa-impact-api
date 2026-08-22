<?php

use App\Models\Partner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsCommunicationForPartners(): User
{
    $user = User::factory()->create();
    $user->assignRole('communication');

    return $user;
}

it("permet à communication de créer un partenaire", function () {
    $user = actingAsCommunicationForPartners();

    $response = $this->actingAs($user)->postJson('/api/admin/partners', [
        'nom' => 'Fondation Sénégal Avenir',
        'type' => 'financier',
        'lien' => 'https://example.org',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.nom', 'Fondation Sénégal Avenir')
        ->assertJsonPath('data.type', 'financier')
        ->assertJsonPath('data.statut', 'actif');
});

it("refuse la création d'un partenaire à un rôle sans permission partners.create", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->postJson('/api/admin/partners', [
        'nom' => 'Partenaire Test',
    ])->assertForbidden();
});

it("rejette un lien qui n'est pas une URL valide", function () {
    $user = actingAsCommunicationForPartners();

    $this->actingAs($user)->postJson('/api/admin/partners', [
        'nom' => 'Partenaire Test',
        'lien' => 'pas-une-url',
    ])->assertStatus(422)->assertJsonValidationErrors('lien');
});

it("permet à communication de mettre à jour le statut et l'ordre d'un partenaire", function () {
    $user = actingAsCommunicationForPartners();
    $partner = Partner::factory()->create(['statut' => 'actif', 'ordre' => 0]);

    $this->actingAs($user)
        ->putJson("/api/admin/partners/{$partner->id}", ['statut' => 'inactif', 'ordre' => 3])
        ->assertOk()
        ->assertJsonPath('data.statut', 'inactif')
        ->assertJsonPath('data.ordre', 3);
});

it("liste les partenaires côté admin sans filtrer par statut", function () {
    $user = actingAsCommunicationForPartners();
    Partner::factory()->create(['statut' => 'actif']);
    Partner::factory()->create(['statut' => 'inactif']);

    $response = $this->actingAs($user)->getJson('/api/admin/partners');

    $response->assertOk()->assertJsonCount(2, 'data');
});

it("permet à communication d'exporter les partenaires en CSV", function () {
    $user = actingAsCommunicationForPartners();
    Partner::factory()->count(3)->create();

    $response = $this->actingAs($user)->get('/api/admin/partners/export');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it("refuse l'export des partenaires à un rôle sans permission partners.view", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->get('/api/admin/partners/export')->assertForbidden();
});

it("supprime un partenaire", function () {
    $user = actingAsCommunicationForPartners();
    $partner = Partner::factory()->create();

    $this->actingAs($user)->deleteJson("/api/admin/partners/{$partner->id}")->assertOk();

    $this->assertDatabaseMissing('partners', ['id' => $partner->id]);
});

it("exige une authentification pour accéder à la liste des partenaires admin", function () {
    $this->getJson('/api/admin/partners')->assertUnauthorized();
});
