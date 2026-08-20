<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seede (ou met à jour) le compte administrateur-principal à partir de
 * `config('admin.seed_*')` (voir config/admin.php) — pour ne plus avoir à
 * relancer la commande interactive `admin:create` après chaque
 * `migrate:fresh` (demande explicite du 2026-08-20).
 *
 * Volontairement AUCUN identifiant/mot de passe en dur dans le code : si
 * ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD ne sont pas définis dans le .env
 * local, le seeder ne fait rien (et prévient dans la console) plutôt que de
 * créer un compte avec un mot de passe faible ou devinable qui finirait
 * versionné avec le reste du code.
 *
 * Passe par config() plutôt que par env() directement (voir config/admin.php
 * pour le raisonnement complet) — c'est ce qui rend ce seeder fiablement
 * testable.
 *
 * Idempotent : `updateOrCreate` sur l'email, donc rejouable sans créer de
 * doublon — si le compte existe déjà (ex. créé via `admin:create`), son mot
 * de passe/nom est simplement resynchronisé avec le .env.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.seed_email');
        $password = config('admin.seed_password');
        $name = config('admin.seed_name');

        if (! $email || ! $password) {
            $this->command?->warn(
                'AdminUserSeeder ignoré : définis ADMIN_SEED_EMAIL et ADMIN_SEED_PASSWORD dans ton .env '.
                'pour que le compte administrateur-principal soit (re)créé automatiquement à chaque db:seed.'
            );

            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)]
        );

        if (! $user->hasRole('administrateur-principal')) {
            $user->assignRole('administrateur-principal');
        }

        $this->command?->info("Compte administrateur-principal prêt : {$email}");
    }
}
