<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Surcharge via config(), pas putenv() : une fois qu'un vrai .env a chargé
// ADMIN_SEED_EMAIL/PASSWORD dans $_ENV au démarrage de l'app (ce qui est le
// cas chez toi depuis que tu les as ajoutés), putenv() perd la priorité face
// à cette valeur déjà chargée — config() n'a pas ce problème, une
// application fraîche est démarrée à chaque test.
beforeEach(function () {
    config(['admin.seed_email' => null, 'admin.seed_password' => null, 'admin.seed_name' => 'Administrateur Principal']);
});

it("crée le compte administrateur-principal si les variables d'environnement sont définies", function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    config([
        'admin.seed_email' => 'admin@casaimpact.test',
        'admin.seed_password' => 'Password123!',
        'admin.seed_name' => 'Admin Test',
    ]);

    $this->seed(AdminUserSeeder::class);

    $user = User::where('email', 'admin@casaimpact.test')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('administrateur-principal'))->toBeTrue();
});

it("ne crée aucun compte si les variables d'environnement sont absentes", function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->seed(AdminUserSeeder::class);

    expect(User::count())->toBe(0);
});

it("est rejouable sans dupliquer le compte (idempotent)", function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    config([
        'admin.seed_email' => 'admin@casaimpact.test',
        'admin.seed_password' => 'Password123!',
    ]);

    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'admin@casaimpact.test')->count())->toBe(1);
});
