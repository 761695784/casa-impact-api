<?php

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("liste uniquement les partenaires actifs, triés par ordre", function () {
    Partner::factory()->create(['nom' => 'Partenaire Inactif', 'statut' => 'inactif', 'ordre' => 0]);
    Partner::factory()->create(['nom' => 'Partenaire B', 'statut' => 'actif', 'ordre' => 2]);
    Partner::factory()->create(['nom' => 'Partenaire A', 'statut' => 'actif', 'ordre' => 1]);

    $response = $this->getJson('/api/public/partners');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.nom', 'Partenaire A')
        ->assertJsonPath('data.1.nom', 'Partenaire B');
});

it("n'expose pas de route de détail public pour un partenaire", function () {
    $partner = Partner::factory()->create(['statut' => 'actif']);

    $this->getJson("/api/public/partners/{$partner->id}")->assertNotFound();
});
