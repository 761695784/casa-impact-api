<?php

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsGestionnaireCandidaturesForApplications(): User
{
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    return $user;
}

it("permet à gestionnaire-candidatures de lister les candidatures", function () {
    $user = actingAsGestionnaireCandidaturesForApplications();
    Application::factory()->count(3)->create();

    $this->actingAs($user)->getJson('/api/admin/applications')
        ->assertOk()->assertJsonCount(3, 'data');
});

it("refuse la liste des candidatures à communication (pas de permission applications.view)", function () {
    $user = User::factory()->create();
    $user->assignRole('communication');

    $this->actingAs($user)->getJson('/api/admin/applications')->assertForbidden();
});

it("filtre les candidatures par statut", function () {
    $user = actingAsGestionnaireCandidaturesForApplications();
    Application::factory()->create(['statut' => 'nouvelle']);
    Application::factory()->create(['statut' => 'en_liste_attente']);

    $response = $this->actingAs($user)->getJson('/api/admin/applications?statut=en_liste_attente');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it("met à jour uniquement le statut d'une candidature", function () {
    $user = actingAsGestionnaireCandidaturesForApplications();
    $application = Application::factory()->create(['statut' => 'nouvelle', 'nom' => 'Diatta']);

    $response = $this->actingAs($user)->putJson("/api/admin/applications/{$application->id}", [
        'statut' => 'en_cours_etude',
    ]);

    $response->assertOk()->assertJsonPath('data.statut', 'en_cours_etude');
    // Le nom n'est pas modifiable via cet endpoint (non présent dans
    // UpdateApplicationRequest) — inchangé.
    expect($application->fresh()->nom)->toBe('Diatta');
});

it("rejette un statut hors de la liste fixe", function () {
    $user = actingAsGestionnaireCandidaturesForApplications();
    $application = Application::factory()->create();

    $this->actingAs($user)->putJson("/api/admin/applications/{$application->id}", ['statut' => 'inconnu'])
        ->assertStatus(422)->assertJsonValidationErrors('statut');
});

it("fait sortir une candidature de la liste d'attente en un clic", function () {
    $user = actingAsGestionnaireCandidaturesForApplications();
    $application = Application::factory()->create(['statut' => 'en_liste_attente']);

    $response = $this->actingAs($user)->postJson("/api/admin/applications/{$application->id}/promote");

    $response->assertOk()->assertJsonPath('data.statut', 'nouvelle');
    expect($application->fresh()->statut->value)->toBe('nouvelle');
});

it("refuse de promouvoir une candidature qui n'est pas en liste d'attente", function () {
    $user = actingAsGestionnaireCandidaturesForApplications();
    $application = Application::factory()->create(['statut' => 'nouvelle']);

    $this->actingAs($user)->postJson("/api/admin/applications/{$application->id}/promote")
        ->assertStatus(409);
});

it("refuse la promotion à un rôle sans permission applications.update", function () {
    $user = User::factory()->create();
    $user->assignRole('communication');
    $application = Application::factory()->create(['statut' => 'en_liste_attente']);

    $this->actingAs($user)->postJson("/api/admin/applications/{$application->id}/promote")
        ->assertForbidden();
});

it("supprime une candidature", function () {
    $user = actingAsGestionnaireCandidaturesForApplications();
    $application = Application::factory()->create();

    $this->actingAs($user)->deleteJson("/api/admin/applications/{$application->id}")->assertOk();

    $this->assertDatabaseMissing('applications', ['id' => $application->id]);
});

it("permet de télécharger un document associé à une candidature", function () {
    Storage::fake('local');
    $user = actingAsGestionnaireCandidaturesForApplications();
    $application = Application::factory()->create();
    Storage::disk('local')->put('application-documents/1/cv.pdf', 'contenu-fictif');
    $document = ApplicationDocument::factory()->create([
        'application_id' => $application->id,
        'chemin' => 'application-documents/1/cv.pdf',
        'nom_original' => 'cv.pdf',
    ]);

    $this->actingAs($user)
        ->get("/api/admin/applications/{$application->id}/documents/{$document->id}/download")
        ->assertOk();
});

it("refuse le téléchargement d'un document n'appartenant pas à la candidature indiquée", function () {
    $user = actingAsGestionnaireCandidaturesForApplications();
    $applicationA = Application::factory()->create();
    $applicationB = Application::factory()->create();
    $document = ApplicationDocument::factory()->create(['application_id' => $applicationB->id]);

    $this->actingAs($user)
        ->get("/api/admin/applications/{$applicationA->id}/documents/{$document->id}/download")
        ->assertNotFound();
});
