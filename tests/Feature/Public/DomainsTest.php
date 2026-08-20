<?php

use App\Models\Domain;
use Database\Seeders\DomainsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("liste les 6 domaines actifs triés par ordre, sans authentification", function () {
    $this->seed(DomainsSeeder::class);

    $response = $this->getJson('/api/public/domains');

    $response->assertOk()->assertJsonCount(6, 'data')
        ->assertJsonPath('data.0.slug', 'jeunesse-leadership');
});

it("n'expose pas un domaine inactif", function () {
    $this->seed(DomainsSeeder::class);
    Domain::where('slug', 'investissement-diaspora')->update(['statut' => 'inactif']);

    $response = $this->getJson('/api/public/domains');

    $response->assertOk()->assertJsonCount(5, 'data');
});
