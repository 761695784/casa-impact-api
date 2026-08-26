<?php

use App\Models\Application;
use App\Models\ContactMessage;
use App\Models\Membership;
use App\Models\User;
use App\Notifications\ApplicationDecided;
use App\Services\MembershipCardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it("envoie ApplicationDecided quand une candidature passe à retenue", function () {
    Notification::fake();
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');
    $application = Application::factory()->create(['statut' => 'en_cours_etude']);

    $this->actingAs($user)->putJson("/api/admin/applications/{$application->id}", ['statut' => 'retenue'])
        ->assertOk();

    Notification::assertSentOnDemand(ApplicationDecided::class);
});

it("n'envoie pas ApplicationDecided pour un statut intermédiaire (nouvelle/en_cours_etude)", function () {
    Notification::fake();
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');
    $application = Application::factory()->create(['statut' => 'nouvelle']);

    $this->actingAs($user)->putJson("/api/admin/applications/{$application->id}", ['statut' => 'en_cours_etude'])
        ->assertOk();

    Notification::assertNothingSent();
});

it("n'envoie pas ApplicationDecided une seconde fois si le statut de décision ne change pas", function () {
    Notification::fake();
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');
    $application = Application::factory()->create(['statut' => 'retenue']);

    $this->actingAs($user)->putJson("/api/admin/applications/{$application->id}", ['statut' => 'retenue'])
        ->assertOk();

    Notification::assertNothingSent();
});

it("envoie une réponse automatique à la soumission du formulaire de contact", function () {
    Notification::fake();

    $this->postJson('/api/public/contact', [
        'categorie' => 'information_generale',
        'nom' => 'Fatou Sané',
        'email' => 'fatou@example.com',
        'message' => 'Bonjour, je souhaite en savoir plus sur vos programmes.',
    ])->assertCreated();

    $contactMessage = ContactMessage::first();
    expect($contactMessage)->not->toBeNull();
    Notification::assertSentOnDemand(\App\Notifications\ContactMessageReceived::class);
});

it("génère un PDF non vide pour la carte de membre", function () {
    $membership = Membership::factory()->create([
        'statut' => 'validee',
        'validated_at' => now(),
        'nom_complet' => 'Awa Diatta',
        'region' => 'ziguinchor',
    ]);

    $pdf = app(MembershipCardService::class)->generate($membership);

    // Signature %PDF- : suffit à vérifier qu'un vrai document PDF a été
    // produit par DomPDF, sans dépendre d'un lecteur PDF dans les tests.
    expect(substr($pdf, 0, 5))->toBe('%PDF-');
});
