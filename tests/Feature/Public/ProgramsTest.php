<?php

use App\Models\Domain;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("liste uniquement les programmes publiés", function () {
    Program::factory()->create(['statut' => 'publie', 'titre' => 'Bootcamp']);
    Program::factory()->create(['statut' => 'brouillon', 'titre' => 'Brouillon interne']);
    Program::factory()->create(['statut' => 'archive', 'titre' => 'Ancien programme']);

    $response = $this->getJson('/api/public/programs');

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.titre', 'Bootcamp');
});

it("retourne le détail d'un programme publié par son slug", function () {
    Program::factory()->create(['statut' => 'publie', 'slug' => 'bootcamp-2026', 'titre' => 'Bootcamp 2026']);

    $this->getJson('/api/public/programs/bootcamp-2026')
        ->assertOk()
        ->assertJsonPath('data.titre', 'Bootcamp 2026');
});

it("retourne 404 pour un programme en brouillon consulté par son slug", function () {
    Program::factory()->create(['statut' => 'brouillon', 'slug' => 'programme-cache']);

    $this->getJson('/api/public/programs/programme-cache')->assertNotFound();
});

it("filtre les programmes publiés par slug de domaine", function () {
    $domain = Domain::factory()->create(['slug' => 'culture-patrimoine']);
    Program::factory()->create(['statut' => 'publie', 'domain_id' => $domain->id]);
    Program::factory()->create(['statut' => 'publie']);

    $response = $this->getJson('/api/public/programs?domain=culture-patrimoine');

    $response->assertOk()->assertJsonCount(1, 'data');
});
