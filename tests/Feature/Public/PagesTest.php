<?php

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("liste uniquement les pages publiées", function () {
    Page::factory()->create(['statut' => 'publie', 'titre' => 'À propos']);
    Page::factory()->create(['statut' => 'brouillon', 'titre' => 'Brouillon interne']);
    Page::factory()->create(['statut' => 'archive', 'titre' => 'Ancienne page']);

    $response = $this->getJson('/api/public/pages');

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.titre', 'À propos');
});

it("retourne le détail d'une page publiée par son slug", function () {
    Page::factory()->create(['statut' => 'publie', 'slug' => 'notre-mission', 'titre' => 'Notre mission']);

    $this->getJson('/api/public/pages/notre-mission')
        ->assertOk()
        ->assertJsonPath('data.titre', 'Notre mission');
});

it("retourne 404 pour une page en brouillon consultée par son slug", function () {
    Page::factory()->create(['statut' => 'brouillon', 'slug' => 'page-cachee']);

    $this->getJson('/api/public/pages/page-cachee')->assertNotFound();
});
