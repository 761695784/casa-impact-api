<?php

use App\Models\Membership;
use App\Notifications\MembershipReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function membershipPayload(array $overrides = []): array
{
    return [
        'nom_complet' => 'Awa Diatta',
        'email' => 'awa.diatta@example.com',
        'telephone' => '+221701112233',
        'region' => 'ziguinchor',
        'departement' => 'Ziguinchor',
        'engagement_moral' => true,
        'photo' => UploadedFile::fake()->image('photo.jpg'),
        ...$overrides,
    ];
}

it("soumet une demande d'adhésion valide et reçoit un numéro de dossier", function () {
    Storage::fake('public');
    Notification::fake();

    $response = $this->postJson('/api/public/memberships', membershipPayload());

    $response->assertCreated()->assertJsonPath('data.statut', 'en_attente_paiement');
    expect($response->json('data.numero_membre'))->toMatch('/^CI-\d{4}-\d{6}$/');
});

it("stocke la photo envoyée sur le disque public", function () {
    Storage::fake('public');
    Notification::fake();

    $this->postJson('/api/public/memberships', membershipPayload())->assertCreated();

    $membership = Membership::first();
    Storage::disk('public')->assertExists($membership->photo_path);
});

it("exige le département pour une région de Casamance", function () {
    Storage::fake('public');

    $payload = membershipPayload(['region' => 'kolda']);
    unset($payload['departement']);

    $this->postJson('/api/public/memberships', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('departement');
});

it("n'exige pas le département pour Dakar ou la diaspora", function () {
    Storage::fake('public');
    Notification::fake();

    $payload = membershipPayload(['region' => 'dakar']);
    unset($payload['departement']);

    $this->postJson('/api/public/memberships', $payload)->assertCreated();
});

it("exige la photo et l'engagement moral", function () {
    Storage::fake('public');

    $payload = membershipPayload();
    unset($payload['photo'], $payload['engagement_moral']);

    $this->postJson('/api/public/memberships', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['photo', 'engagement_moral']);
});

it("génère des numéros de dossier séquentiels indépendants de ceux des candidatures", function () {
    Storage::fake('public');
    Notification::fake();

    $first = $this->postJson('/api/public/memberships', membershipPayload())->json('data.numero_membre');
    $second = $this->postJson('/api/public/memberships', membershipPayload(['email' => 'autre@example.com']))->json('data.numero_membre');

    preg_match('/CI-\d{4}-(\d{6})/', $first, $m1);
    preg_match('/CI-\d{4}-(\d{6})/', $second, $m2);
    expect((int) $m2[1])->toBe((int) $m1[1] + 1);
});

it("envoie une notification de réception avec instructions de paiement", function () {
    Storage::fake('public');
    Notification::fake();

    $this->postJson('/api/public/memberships', membershipPayload())->assertCreated();

    Notification::assertSentOnDemand(MembershipReceived::class);
});
