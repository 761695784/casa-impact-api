<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // administrateur-principal a un accès total, quelle que soit la
        // permission demandée — SAUF les deux règles non contournables
        // (auto-suppression, auto-changement de rôle) qui sont réaffirmées
        // explicitement dans UserController/UserPolicy et ne passent jamais
        // par ce court-circuit (elles vérifient `$actor->is($target)` avant
        // toute question de permission).
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('administrateur-principal') ? true : null;
        });

        // Politique de mot de passe par défaut (décision validée : "fort par
        // défaut Laravel") appliquée partout où `Password::defaults()` est
        // utilisé (StoreUserRequest, UpdateUserRequest, admin:create).
        Password::defaults(function () {
            return Password::min(8)->mixedCase()->numbers()->symbols();
        });
    }
}
