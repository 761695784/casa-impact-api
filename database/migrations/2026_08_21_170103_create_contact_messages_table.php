<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 16 (Contact). Formulaire de contact public — `statut` par défaut
 * `nouveau` en base (redondant avec le forçage applicatif dans
 * Public\ContactMessageController::store(), mais garantit la cohérence même
 * en cas d'insertion hors de ce contrôleur, ex. seeder/tinker).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            // Colonne contrôlée côté application (App\Enums\ContactCategory).
            $table->string('categorie');
            $table->string('nom');
            $table->string('email');
            $table->string('telephone')->nullable();
            $table->string('sujet')->nullable();
            $table->text('message');
            // Colonne contrôlée côté application (App\Enums\ContactMessageStatus).
            $table->string('statut')->default('nouveau');
            $table->timestamps();

            $table->index('statut');
            $table->index('categorie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
