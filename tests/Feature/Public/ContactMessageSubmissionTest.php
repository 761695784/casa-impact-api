<?php

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function baseContactPayload(): array
{
    return [
        'categorie' => 'partenariat',
        'nom' => 'Fatou Ndiaye',
        'email' => 'fatou.ndiaye@example.com',
        'telephone' => '+221771234567',
        'sujet' => 'Proposition de partenariat',
        'message' => 'Bonjour, nous souhaitons explorer un partenariat avec Casa Impact.',
    ];
}

it("soumet un message de contact valide et le crée avec le statut nouveau", function () {
    $response = $this->postJson('/api/public/contact', baseContactPayload());

    $response->assertCreated()
        ->assertJsonPath('message', 'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.');

    $this->assertDatabaseHas('contact_messages', [
        'email' => 'fatou.ndiaye@example.com',
        'statut' => 'nouveau',
    ]);
});

it("ignore un statut envoyé par le client et le force à nouveau", function () {
    $response = $this->postJson('/api/public/contact', [
        ...baseContactPayload(),
        'statut' => 'traite',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('contact_messages', ['statut' => 'nouveau']);
});

it("ne renvoie pas les données du message dans la réponse", function () {
    $response = $this->postJson('/api/public/contact', baseContactPayload());

    $response->assertCreated();
    expect($response->json('data'))->toBeNull();
});

it("rejette une soumission sans message", function () {
    $payload = baseContactPayload();
    unset($payload['message']);

    $this->postJson('/api/public/contact', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('message');
});

it("rejette une catégorie hors de la liste fixe", function () {
    $this->postJson('/api/public/contact', [
        ...baseContactPayload(),
        'categorie' => 'inconnue',
    ])->assertStatus(422)->assertJsonValidationErrors('categorie');
});

it("aucun message n'est créé quand la validation échoue", function () {
    $payload = baseContactPayload();
    unset($payload['email']);

    $this->postJson('/api/public/contact', $payload)->assertStatus(422);

    expect(ContactMessage::count())->toBe(0);
});
