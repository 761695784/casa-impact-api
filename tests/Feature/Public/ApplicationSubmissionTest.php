<?php

use App\Models\Application;
use App\Models\ApplicationCall;
use App\Notifications\ApplicationSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function basePayload(ApplicationCall $call): array
{
    return [
        'application_call_id' => $call->id,
        'nom' => 'Diatta',
        'prenom' => 'Awa',
        'email' => 'awa.diatta@example.com',
        'region' => 'ziguinchor',
        // Obligatoires depuis la décision du 2026-08-20 (StoreApplicationRequest).
        'tranche_age' => '18-25',
        'niveau_etudes' => 'licence',
    ];
}

it("soumet une candidature valide à un appel publié avec des places disponibles", function () {
    Notification::fake();
    $call = ApplicationCall::factory()->create(['statut' => 'publie', 'nombre_places' => 10, 'date_limite' => null]);

    $response = $this->postJson('/api/public/applications', basePayload($call));

    $response->assertCreated()
        ->assertJsonPath('data.statut', 'nouvelle');
    expect($response->json('data.reference'))->toMatch('/^CI-\d{4}-\d{6}$/');
});

it("place une candidature en liste d'attente quand le quota est atteint", function () {
    Notification::fake();
    $call = ApplicationCall::factory()->create(['statut' => 'publie', 'nombre_places' => 1, 'date_limite' => null]);
    Application::factory()->create(['application_call_id' => $call->id, 'statut' => 'nouvelle']);

    $response = $this->postJson('/api/public/applications', basePayload($call));

    $response->assertCreated()->assertJsonPath('data.statut', 'en_liste_attente');
});

it("ne compte pas les candidatures déjà en liste d'attente contre le quota", function () {
    Notification::fake();
    $call = ApplicationCall::factory()->create(['statut' => 'publie', 'nombre_places' => 1, 'date_limite' => null]);
    Application::factory()->create(['application_call_id' => $call->id, 'statut' => 'en_liste_attente']);

    $response = $this->postJson('/api/public/applications', basePayload($call));

    $response->assertCreated()->assertJsonPath('data.statut', 'nouvelle');
});

it("rejette une soumission sans tranche_age ni niveau_etudes", function () {
    $call = ApplicationCall::factory()->create(['statut' => 'publie', 'date_limite' => null]);
    $payload = basePayload($call);
    unset($payload['tranche_age'], $payload['niveau_etudes']);

    $this->postJson('/api/public/applications', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['tranche_age', 'niveau_etudes']);
});

it("refuse la soumission à un appel non publié", function () {
    $call = ApplicationCall::factory()->create(['statut' => 'brouillon']);

    $this->postJson('/api/public/applications', basePayload($call))->assertNotFound();
});

it("refuse la soumission après la date limite", function () {
    $call = ApplicationCall::factory()->create(['statut' => 'publie', 'date_limite' => now()->subDay()]);

    $this->postJson('/api/public/applications', basePayload($call))->assertStatus(409);
});

it("rejette une soumission sans les documents requis par l'appel", function () {
    $call = ApplicationCall::factory()->create([
        'statut' => 'publie',
        'date_limite' => null,
        'documents_requis' => ['cv', 'piece_identite'],
    ]);

    $this->postJson('/api/public/applications', basePayload($call))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['documents.cv', 'documents.piece_identite']);
});

it("stocke les documents envoyés et les associe à la candidature", function () {
    Storage::fake('local');
    Notification::fake();
    $call = ApplicationCall::factory()->create([
        'statut' => 'publie',
        'date_limite' => null,
        'documents_requis' => ['cv'],
    ]);

    $response = $this->postJson('/api/public/applications', [
        ...basePayload($call),
        'documents' => [
            'cv' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
        ],
    ]);

    $response->assertCreated();
    $application = Application::where('reference', $response->json('data.reference'))->firstOrFail();

    expect($application->documents()->count())->toBe(1);
    $document = $application->documents()->first();
    expect($document->type)->toBe('cv');
    Storage::disk('local')->assertExists($document->chemin);
});

it("génère des références séquentielles uniques pour deux soumissions", function () {
    Notification::fake();
    $call = ApplicationCall::factory()->create(['statut' => 'publie', 'date_limite' => null]);

    $first = $this->postJson('/api/public/applications', basePayload($call))->json('data.reference');
    $second = $this->postJson('/api/public/applications', [...basePayload($call), 'email' => 'autre@example.com'])->json('data.reference');

    expect($first)->not->toBe($second);

    preg_match('/CI-\d{4}-(\d{6})/', $first, $m1);
    preg_match('/CI-\d{4}-(\d{6})/', $second, $m2);
    expect((int) $m2[1])->toBe((int) $m1[1] + 1);
});

it("envoie une notification de confirmation par email", function () {
    Notification::fake();
    $call = ApplicationCall::factory()->create(['statut' => 'publie', 'date_limite' => null]);

    $this->postJson('/api/public/applications', basePayload($call))->assertCreated();

    Notification::assertSentOnDemand(ApplicationSubmitted::class);
});
