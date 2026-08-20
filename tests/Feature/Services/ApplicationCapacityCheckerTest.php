<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationCall;
use App\Services\ApplicationCapacityChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("retourne Nouvelle quand nombre_places est illimité (null)", function () {
    $call = ApplicationCall::factory()->create(['nombre_places' => null]);

    $statut = app(ApplicationCapacityChecker::class)->determineStatus($call);

    expect($statut)->toBe(ApplicationStatus::Nouvelle);
});

it("retourne Nouvelle tant que le quota n'est pas atteint", function () {
    $call = ApplicationCall::factory()->create(['nombre_places' => 2]);
    Application::factory()->create(['application_call_id' => $call->id, 'statut' => 'nouvelle']);

    $statut = app(ApplicationCapacityChecker::class)->determineStatus($call);

    expect($statut)->toBe(ApplicationStatus::Nouvelle);
});

it("retourne EnListeAttente une fois le quota atteint", function () {
    $call = ApplicationCall::factory()->create(['nombre_places' => 1]);
    Application::factory()->create(['application_call_id' => $call->id, 'statut' => 'nouvelle']);

    $statut = app(ApplicationCapacityChecker::class)->determineStatus($call);

    expect($statut)->toBe(ApplicationStatus::EnListeAttente);
});

it("ne compte pas les candidatures déjà en liste d'attente contre le quota", function () {
    $call = ApplicationCall::factory()->create(['nombre_places' => 1]);
    Application::factory()->create(['application_call_id' => $call->id, 'statut' => 'en_liste_attente']);

    $statut = app(ApplicationCapacityChecker::class)->determineStatus($call);

    expect($statut)->toBe(ApplicationStatus::Nouvelle);
});
