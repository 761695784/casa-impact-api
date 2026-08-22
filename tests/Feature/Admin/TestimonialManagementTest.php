<?php

use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsCommunicationForTestimonials(): User
{
    $user = User::factory()->create();
    $user->assignRole('communication');

    return $user;
}

it("permet à communication de créer un témoignage", function () {
    $user = actingAsCommunicationForTestimonials();

    $response = $this->actingAs($user)->postJson('/api/admin/testimonials', [
        'auteur' => 'Bineta Camara',
        'citation' => "Ce programme m'a permis de lancer mon activité.",
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.auteur', 'Bineta Camara')
        ->assertJsonPath('data.statut', 'brouillon');
});

it("refuse la création d'un témoignage à un rôle sans permission testimonials.create", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->postJson('/api/admin/testimonials', [
        'auteur' => 'Cheikh Balde',
        'citation' => 'Un témoignage.',
    ])->assertForbidden();
});

it("rejette la création d'un témoignage sans citation", function () {
    $user = actingAsCommunicationForTestimonials();

    $this->actingAs($user)->postJson('/api/admin/testimonials', [
        'auteur' => 'Khady Diallo',
    ])->assertStatus(422)->assertJsonValidationErrors('citation');
});

it("permet de publier un témoignage via l'update standard (pas d'endpoint dédié)", function () {
    $user = actingAsCommunicationForTestimonials();
    $testimonial = Testimonial::factory()->create(['statut' => 'brouillon']);

    $this->actingAs($user)
        ->putJson("/api/admin/testimonials/{$testimonial->id}", ['statut' => 'publie'])
        ->assertOk()
        ->assertJsonPath('data.statut', 'publie');
});

it("permet à communication d'exporter les témoignages en CSV", function () {
    $user = actingAsCommunicationForTestimonials();
    Testimonial::factory()->count(3)->create();

    $response = $this->actingAs($user)->get('/api/admin/testimonials/export');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it("refuse l'export des témoignages à un rôle sans permission testimonials.view", function () {
    $user = User::factory()->create();
    $user->assignRole('gestionnaire-candidatures');

    $this->actingAs($user)->get('/api/admin/testimonials/export')->assertForbidden();
});

it("supprime un témoignage", function () {
    $user = actingAsCommunicationForTestimonials();
    $testimonial = Testimonial::factory()->create();

    $this->actingAs($user)->deleteJson("/api/admin/testimonials/{$testimonial->id}")->assertOk();

    $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
});
