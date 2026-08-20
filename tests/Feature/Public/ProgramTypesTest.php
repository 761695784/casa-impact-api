<?php

use App\Models\ProgramType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("liste les types de programme sans authentification", function () {
    ProgramType::factory()->count(3)->create();

    $response = $this->getJson('/api/public/program-types');

    $response->assertOk()->assertJsonCount(3, 'data');
});
