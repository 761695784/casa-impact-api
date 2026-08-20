<?php

use App\Models\News;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsCommunicationForNews(): User
{
    $user = User::factory()->create();
    $user->assignRole('communication');

    return $user;
}

it("permet à communication de créer une actualité avec un slug généré automatiquement", function () {
    $user = actingAsCommunicationForNews();

    $response = $this->actingAs($user)->postJson('/api/admin/news', [
        'titre' => 'Lancement du programme 2026',
        'type' => 'annonce',
        'corps' => 'Contenu de l\'annonce.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.slug', 'lancement-du-programme-2026')
        ->assertJsonPath('data.statut', 'brouillon')
        ->assertJsonPath('data.type', 'annonce');
});

it("refuse la création d'une actualité à un rôle sans permission news.create", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->postJson('/api/admin/news', [
        'titre' => 'Titre',
        'type' => 'article',
        'corps' => 'contenu',
    ])->assertForbidden();
});

it("rejette un type hors de la liste fixe", function () {
    $user = actingAsCommunicationForNews();

    $this->actingAs($user)->postJson('/api/admin/news', [
        'titre' => 'Titre',
        'type' => 'inconnu',
        'corps' => 'contenu',
    ])->assertStatus(422)->assertJsonValidationErrors('type');
});

it("rejette un slug déjà utilisé par une autre actualité", function () {
    $user = actingAsCommunicationForNews();
    News::factory()->create(['slug' => 'notre-actualite']);

    $this->actingAs($user)->postJson('/api/admin/news', [
        'titre' => 'Notre actualité',
        'slug' => 'notre-actualite',
        'type' => 'article',
        'corps' => 'contenu',
    ])->assertStatus(422)->assertJsonValidationErrors('slug');
});

it("permet de faire passer une actualité en prévisualisation puis publiée via l'update standard", function () {
    $user = actingAsCommunicationForNews();
    $news = News::factory()->create(['statut' => 'brouillon']);

    $this->actingAs($user)
        ->putJson("/api/admin/news/{$news->id}", ['statut' => 'previsualisation'])
        ->assertOk()->assertJsonPath('data.statut', 'previsualisation');

    $this->actingAs($user)
        ->putJson("/api/admin/news/{$news->id}", ['statut' => 'publie'])
        ->assertOk()->assertJsonPath('data.statut', 'publie');
});

it("filtre la liste des actualités par type", function () {
    $user = actingAsCommunicationForNews();
    News::factory()->create(['type' => 'communique']);
    News::factory()->create(['type' => 'article']);

    $response = $this->actingAs($user)->getJson('/api/admin/news?type=communique');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it("supprime une actualité", function () {
    $user = actingAsCommunicationForNews();
    $news = News::factory()->create();

    $this->actingAs($user)->deleteJson("/api/admin/news/{$news->id}")->assertOk();

    $this->assertDatabaseMissing('news', ['id' => $news->id]);
});
