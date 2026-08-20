<?php

use App\Models\ApplicationCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("liste uniquement les appels publiés", function () {
    ApplicationCall::factory()->create(['statut' => 'publie', 'titre' => 'Appel ouvert']);
    ApplicationCall::factory()->create(['statut' => 'brouillon', 'titre' => 'Brouillon interne']);
    ApplicationCall::factory()->create(['statut' => 'ferme', 'titre' => 'Appel fermé']);

    $response = $this->getJson('/api/public/application-calls');

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.titre', 'Appel ouvert');
});

it("retourne le détail d'un appel publié par son slug", function () {
    ApplicationCall::factory()->create(['statut' => 'publie', 'slug' => 'bootcamp-2026', 'titre' => 'Bootcamp 2026']);

    $this->getJson('/api/public/application-calls/bootcamp-2026')
        ->assertOk()
        ->assertJsonPath('data.titre', 'Bootcamp 2026');
});

it("retourne 404 pour un appel en brouillon consulté par son slug", function () {
    ApplicationCall::factory()->create(['statut' => 'brouillon', 'slug' => 'appel-cache']);

    $this->getJson('/api/public/application-calls/appel-cache')->assertNotFound();
});

it("filtre les appels publiés par région", function () {
    ApplicationCall::factory()->create(['statut' => 'publie', 'region' => 'sedhiou']);
    ApplicationCall::factory()->create(['statut' => 'publie', 'region' => 'kolda']);

    $response = $this->getJson('/api/public/application-calls?region=sedhiou');

    $response->assertOk()->assertJsonCount(1, 'data');
});
