<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remplace la migration par défaut `0001_01_01_000000_create_users_table.php`
 * générée par `laravel new`. Différences par rapport au squelette par défaut :
 *   - ajout de `softDeletes()` (règle métier : jamais de suppression définitive
 *     d'un compte admin depuis l'API, cf. architecturev1.md §Module Administration)
 *   - suppression de la table `password_reset_tokens` : aucune inscription/
 *     réinitialisation publique n'existe sur ce projet (pas de compte visiteur,
 *     bootstrap admin exclusivement via `php artisan admin:create`).
 *
 * Les tables `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`,
 * `failed_jobs` du squelette par défaut sont conservées telles quelles
 * (nécessaires : sessions pour Sanctum SPA, jobs pour l'email de confirmation
 * de candidature en file — module Candidatures, à venir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
