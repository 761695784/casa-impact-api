<?php

use App\Models\ContactMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsCommunicationForContactMessages(): User
{
    $user = User::factory()->create();
    $user->assignRole('communication');

    return $user;
}

it("exige une authentification pour lister les messages de contact", function () {
    $this->getJson('/api/admin/contact-messages')->assertUnauthorized();
});

it("refuse la liste des messages de contact à un rôle sans permission contact-messages.view", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)
        ->getJson('/api/admin/contact-messages')
        ->assertForbidden();
});

it("permet à communication de lister les messages de contact", function () {
    $user = actingAsCommunicationForContactMessages();
    ContactMessage::factory()->count(3)->create();

    $this->actingAs($user)
        ->getJson('/api/admin/contact-messages')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it("permet à communication de marquer un message comme traité", function () {
    $user = actingAsCommunicationForContactMessages();
    $message = ContactMessage::factory()->create(['statut' => 'nouveau']);

    $this->actingAs($user)
        ->putJson("/api/admin/contact-messages/{$message->id}", ['statut' => 'traite'])
        ->assertOk()
        ->assertJsonPath('data.statut', 'traite');

    $this->assertDatabaseHas('contact_messages', ['id' => $message->id, 'statut' => 'traite']);
});

it("ignore toute tentative de modifier le contenu du message via l'update admin", function () {
    $user = actingAsCommunicationForContactMessages();
    $message = ContactMessage::factory()->create(['nom' => 'Nom Original', 'statut' => 'nouveau']);

    $this->actingAs($user)
        ->putJson("/api/admin/contact-messages/{$message->id}", [
            'statut' => 'traite',
            'nom' => 'Nom Modifié',
        ])
        ->assertOk();

    $this->assertDatabaseHas('contact_messages', ['id' => $message->id, 'nom' => 'Nom Original']);
});

it("filtre les messages de contact par statut", function () {
    $user = actingAsCommunicationForContactMessages();
    ContactMessage::factory()->create(['statut' => 'nouveau']);
    ContactMessage::factory()->create(['statut' => 'traite']);

    $response = $this->actingAs($user)->getJson('/api/admin/contact-messages?statut=traite');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it("permet à communication d'exporter les messages de contact en CSV", function () {
    $user = actingAsCommunicationForContactMessages();
    ContactMessage::factory()->count(3)->create();

    $response = $this->actingAs($user)->get('/api/admin/contact-messages/export');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it("refuse l'export des messages de contact à un rôle sans permission contact-messages.view", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->get('/api/admin/contact-messages/export')->assertForbidden();
});

it("supprime un message de contact", function () {
    $user = actingAsCommunicationForContactMessages();
    $message = ContactMessage::factory()->create();

    $this->actingAs($user)->deleteJson("/api/admin/contact-messages/{$message->id}")->assertOk();

    $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
});
