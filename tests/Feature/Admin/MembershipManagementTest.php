<?php

use App\Models\Membership;
use App\Models\User;
use App\Notifications\MembershipValidated;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// Nommée différemment de son homonyme éventuel dans UserManagementTest.php
// (Module 1) : Pest charge tous les fichiers de tests dans le même espace
// de noms global pour les fonctions "helper" hors closure — deux fichiers
// déclarant une fonction du même nom provoquent un
// "Cannot redeclare function" fatal au démarrage de la suite complète
// (constaté par l'utilisateur le 2026-08-24 avec `php artisan test`).
function actingAsAdministrateurPrincipalPourAdhesions(): User
{
    $user = User::factory()->create();
    $user->assignRole('administrateur-principal');

    return $user;
}

it("permet à administrateur-principal de lister les adhésions", function () {
    $user = actingAsAdministrateurPrincipalPourAdhesions();
    Membership::factory()->count(3)->create();

    $this->actingAs($user)->getJson('/api/admin/memberships')
        ->assertOk()->assertJsonCount(3, 'data');
});

it("refuse l'accès aux adhésions à communication (permission memberships.* non attribuée)", function () {
    $user = User::factory()->create();
    $user->assignRole('communication');

    $this->actingAs($user)->getJson('/api/admin/memberships')->assertForbidden();
});

it("refuse l'accès aux adhésions à gestionnaire-candidatures (permission memberships.* non attribuée)", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->getJson('/api/admin/memberships')->assertForbidden();
});

it("filtre les adhésions par statut", function () {
    $user = actingAsAdministrateurPrincipalPourAdhesions();
    Membership::factory()->create(['statut' => 'en_attente_paiement']);
    Membership::factory()->create(['statut' => 'validee']);

    $response = $this->actingAs($user)->getJson('/api/admin/memberships?statut=validee');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it("crée une adhésion manuelle directement validée, sans email de bienvenue si demandé", function () {
    Storage::fake('public');
    Notification::fake();
    $user = actingAsAdministrateurPrincipalPourAdhesions();

    $response = $this->actingAs($user)->postJson('/api/admin/memberships', [
        'nom_complet' => 'Malang Marna',
        'email' => 'malang@example.com',
        'telephone' => '+221701112233',
        'region' => 'kolda',
        'departement' => 'Kolda',
        'photo' => UploadedFile::fake()->image('photo.jpg'),
        'send_welcome_email' => false,
    ]);

    $response->assertCreated()->assertJsonPath('data.statut', 'validee')->assertJsonPath('data.source', 'manuel');
    Notification::assertNothingSent();
});

it("envoie l'email de bienvenue avec la carte pour une adhésion manuelle par défaut", function () {
    Storage::fake('public');
    Notification::fake();
    $user = actingAsAdministrateurPrincipalPourAdhesions();

    $this->actingAs($user)->postJson('/api/admin/memberships', [
        'nom_complet' => 'Malang Marna',
        'email' => 'malang@example.com',
        'telephone' => '+221701112233',
        'region' => 'dakar',
        'photo' => UploadedFile::fake()->image('photo.jpg'),
    ])->assertCreated();

    Notification::assertSentOnDemand(MembershipValidated::class);
});

it("valide une adhésion en attente et déclenche l'envoi de la carte", function () {
    Notification::fake();
    $user = actingAsAdministrateurPrincipalPourAdhesions();
    $membership = Membership::factory()->create(['statut' => 'en_attente_paiement']);

    $response = $this->actingAs($user)->putJson("/api/admin/memberships/{$membership->id}", [
        'statut' => 'validee',
    ]);

    $response->assertOk()->assertJsonPath('data.statut', 'validee');
    expect($membership->fresh()->validated_at)->not->toBeNull();
    expect($membership->fresh()->validated_by)->toBe($user->id);
    Notification::assertSentOnDemand(MembershipValidated::class);
    // assertCount() plutôt qu'un compteur "OnDemandTimes" (n'existe pas
    // sur le fake de notification) : vérifie qu'une SEULE notification a
    // été envoyée au total pour cette requête, donc pas de double envoi.
    Notification::assertCount(1);
});

it("n'envoie pas deux fois l'email de validation si l'adhésion est déjà validée", function () {
    Notification::fake();
    $user = actingAsAdministrateurPrincipalPourAdhesions();
    $membership = Membership::factory()->create(['statut' => 'validee', 'validated_at' => now()]);

    $this->actingAs($user)->putJson("/api/admin/memberships/{$membership->id}", [
        'statut' => 'validee',
    ])->assertOk();

    Notification::assertNothingSent();
});

it("supprime une adhésion et sa photo", function () {
    Storage::fake('public');
    Storage::disk('public')->put('membership-photos/photo.jpg', 'contenu-fictif');
    $user = actingAsAdministrateurPrincipalPourAdhesions();
    $membership = Membership::factory()->create(['photo_path' => 'membership-photos/photo.jpg']);

    $this->actingAs($user)->deleteJson("/api/admin/memberships/{$membership->id}")->assertOk();

    $this->assertDatabaseMissing('memberships', ['id' => $membership->id]);
    Storage::disk('public')->assertMissing('membership-photos/photo.jpg');
});

it("exporte les adhésions en CSV", function () {
    $user = actingAsAdministrateurPrincipalPourAdhesions();
    Membership::factory()->count(2)->create();

    $this->actingAs($user)->get('/api/admin/memberships/export')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it("télécharge la carte de membre d'une adhésion validée", function () {
    $user = actingAsAdministrateurPrincipalPourAdhesions();
    $membership = Membership::factory()->create(['statut' => 'validee', 'validated_at' => now()]);

    $this->actingAs($user)->get("/api/admin/memberships/{$membership->id}/card")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it("refuse de télécharger la carte d'une adhésion non encore validée", function () {
    $user = actingAsAdministrateurPrincipalPourAdhesions();
    $membership = Membership::factory()->create(['statut' => 'en_attente_paiement']);

    $this->actingAs($user)->get("/api/admin/memberships/{$membership->id}/card")
        ->assertStatus(409);
});
