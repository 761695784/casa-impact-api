<?php

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("liste uniquement les actualités publiées", function () {
    News::factory()->create(['statut' => 'publie', 'titre' => 'Actualité publiée']);
    News::factory()->create(['statut' => 'brouillon', 'titre' => 'Brouillon interne']);
    News::factory()->create(['statut' => 'previsualisation', 'titre' => 'En relecture']);
    News::factory()->create(['statut' => 'archive', 'titre' => 'Ancienne actualité']);

    $response = $this->getJson('/api/public/news');

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.titre', 'Actualité publiée');
});

it("retourne le détail d'une actualité publiée par son slug", function () {
    News::factory()->create(['statut' => 'publie', 'slug' => 'notre-actualite', 'titre' => 'Notre actualité']);

    $this->getJson('/api/public/news/notre-actualite')
        ->assertOk()
        ->assertJsonPath('data.titre', 'Notre actualité');
});

it("retourne 404 pour une actualité en prévisualisation consultée par son slug", function () {
    News::factory()->create(['statut' => 'previsualisation', 'slug' => 'actualite-cachee']);

    $this->getJson('/api/public/news/actualite-cachee')->assertNotFound();
});

it("filtre les actualités publiées par type", function () {
    News::factory()->create(['statut' => 'publie', 'type' => 'communique']);
    News::factory()->create(['statut' => 'publie', 'type' => 'article']);

    $response = $this->getJson('/api/public/news?type=communique');

    $response->assertOk()->assertJsonCount(1, 'data');
});
