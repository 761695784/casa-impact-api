<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Seul point d'entrée pour créer le tout premier compte admin
 * (`administrateur-principal`) : aucune route d'inscription publique
 * n'existe (règle "pas de compte visiteur", étendue ici à l'admin — le
 * premier compte est créé en ligne de commande par la personne qui
 * déploie le projet, pas via un formulaire web).
 *
 * Les comptes suivants sont créés depuis l'admin lui-même via
 * POST /api/admin/users par un administrateur-principal existant.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create';

    protected $description = "Crée le tout premier compte administrateur-principal (bootstrap, interactif).";

    public function handle(): int
    {
        $this->info('Création du compte administrateur-principal Casa Impact.');

        $name = $this->ask('Nom complet');
        $email = $this->ask('Adresse email');
        $password = $this->secret('Mot de passe');
        $passwordConfirmation = $this->secret('Confirmer le mot de passe');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            $this->error('Impossible de créer le compte :');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('administrateur-principal');

        $this->info("Compte administrateur-principal créé avec succès : {$user->email}");

        return self::SUCCESS;
    }
}
