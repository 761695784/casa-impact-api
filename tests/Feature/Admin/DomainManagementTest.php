<?php

use App\Models\Domain;
use App\Models\User;
use Database\Seeders\DomainsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, DomainsSeeder::class]);
});

it("seede exactement les 6 domaines officiels avec des slugs stables", function () {
    expect(Domain::count())->toBe(6);
    expect(Domain::where('slug', 'jeunesse-leadership')->exists())->toBeTrue();
});

it("permet à communication de mettre à jour le contenu d'un domaine", function () {
    $user = User::factory()->create();
    $user->assignRole('communication');
    $domain = Domain::where('slug', 'culture-patrimoine')->first();

    $response = $this->actingAs($user)->putJson("/api/admin/domains/{$domain->id}", [
        'description' => 'La Casamance, terre de culture et de patrimoine vivant.',
        'ordre' => 3,
    ]);

    $response->assertOk()->assertJsonPath('data.description', 'La Casamance, terre de culture et de patrimoine vivant.');
});

it("n'expose aucune route pour créer ou supprimer un domaine", function () {
    $user = User::factory()->create();
    $user->assignRole('administrateur-principal');

    $this->actingAs($user)->postJson('/api/admin/domains', ['nom' => 'Nouveau domaine'])
        ->assertStatus(405); // Method Not Allowed — la route n'existe simplement pas

    $domain = Domain::first();
    $this->actingAs($user)->deleteJson("/api/admin/domains/{$domain->id}")
        ->assertStatus(405);
});

it("ignore toute tentative de modifier le slug via l'update", function () {
    $user = User::factory()->create();
    $user->assignRole('administrateur-principal');
    $domain = Domain::where('slug', 'sport-talents')->first();

    $this->actingAs($user)->putJson("/api/admin/domains/{$domain->id}", [
        'nom' => 'Sport & Talents',
        'slug' => 'un-nouveau-slug',
    ])->assertOk();

    expect($domain->fresh()->slug)->toBe('sport-talents');
});
