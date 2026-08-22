<?php

use App\Models\Talent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("liste uniquement les talents publiés", function () {
    Talent::factory()->create(['statut' => 'publie', 'nom' => 'Talent publié']);
    Talent::factory()->create(['statut' => 'brouillon', 'nom' => 'Brouillon interne']);
    Talent::factory()->create(['statut' => 'archive', 'nom' => 'Ancien talent']);

    $response = $this->getJson('/api/public/talents');

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nom', 'Talent publié');
});

it("retourne le détail d'un talent publié par son slug", function () {
    Talent::factory()->create(['statut' => 'publie', 'slug' => 'aissatou-diatta', 'nom' => 'Aïssatou Diatta']);

    $this->getJson('/api/public/talents/aissatou-diatta')
        ->assertOk()
        ->assertJsonPath('data.nom', 'Aïssatou Diatta');
});

it("retourne 404 pour un talent non publié consulté par son slug", function () {
    Talent::factory()->create(['statut' => 'brouillon', 'slug' => 'talent-cache']);

    $this->getJson('/api/public/talents/talent-cache')->assertNotFound();
});

it("filtre les talents publiés par région", function () {
    Talent::factory()->create(['statut' => 'publie', 'region' => 'ziguinchor']);
    Talent::factory()->create(['statut' => 'publie', 'region' => 'kolda']);

    $response = $this->getJson('/api/public/talents?region=ziguinchor');

    $response->assertOk()->assertJsonCount(1, 'data');
});
