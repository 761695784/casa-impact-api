<?php

use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("liste uniquement les témoignages publiés", function () {
    Testimonial::factory()->create(['statut' => 'publie', 'auteur' => 'Auteur publié']);
    Testimonial::factory()->create(['statut' => 'brouillon', 'auteur' => 'Brouillon interne']);
    Testimonial::factory()->create(['statut' => 'archive', 'auteur' => 'Ancien témoignage']);

    $response = $this->getJson('/api/public/testimonials');

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.auteur', 'Auteur publié');
});

it("ne propose aucune route de détail pour les témoignages", function () {
    // Volontairement pas de route GET /api/public/testimonials/{id} ni
    // {slug} — un témoignage est une citation courte, jamais affichée sur
    // une page dédiée (voir Api\Public\TestimonialController, pas de show()).
    $testimonial = Testimonial::factory()->create(['statut' => 'publie']);

    $this->getJson("/api/public/testimonials/{$testimonial->id}")->assertNotFound();
});
