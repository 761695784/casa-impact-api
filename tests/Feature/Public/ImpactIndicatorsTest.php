<?php

use App\Models\ImpactIndicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('liste publiquement les indicateurs avec leurs valeurs, sans authentification', function () {
    $indicator = ImpactIndicator::factory()->create(['libelle' => 'Jeunes formés']);
    $indicator->values()->create(['valeur' => 42, 'periode' => '2026']);

    $response = $this->getJson('/api/public/impact-indicators');

    $response->assertOk()
        ->assertJsonPath('data.0.libelle', 'Jeunes formés')
        // PHP encode un float entier (42.0) en JSON sans décimale ("42"),
        // donc une fois décodé côté test c'est un int PHP, pas un float —
        // assertJsonPath compare en strict (===), d'où 42 et non 42.0 ici.
        ->assertJsonPath('data.0.values.0.valeur', 42);
});
