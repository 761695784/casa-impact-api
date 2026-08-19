<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it("crée un compte administrateur-principal via la commande interactive", function () {
    $this->artisan('admin:create')
        ->expectsQuestion('Nom complet', 'Malang Marna')
        ->expectsQuestion('Adresse email', 'malang2019marna@gmail.com')
        ->expectsQuestion('Mot de passe', 'Password123!')
        ->expectsQuestion('Confirmer le mot de passe', 'Password123!')
        ->assertExitCode(0);

    $user = User::where('email', 'malang2019marna@gmail.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('administrateur-principal'))->toBeTrue();
});

it("échoue proprement si le mot de passe ne respecte pas la politique", function () {
    $this->artisan('admin:create')
        ->expectsQuestion('Nom complet', 'Malang Marna')
        ->expectsQuestion('Adresse email', 'malang2019marna@gmail.com')
        ->expectsQuestion('Mot de passe', 'faible')
        ->expectsQuestion('Confirmer le mot de passe', 'faible')
        ->assertExitCode(1);

    expect(User::where('email', 'malang2019marna@gmail.com')->exists())->toBeFalse();
});
