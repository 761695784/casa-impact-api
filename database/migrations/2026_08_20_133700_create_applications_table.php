<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formulaire "Option A" (architecturev1.md §I, décision déjà actée) : champs
 * fixes nullable couvrant l'ensemble borné du brief, pas de table EAV.
 * `statut` inclut `en_liste_attente` — décision validée le 2026-08-20 pour
 * la gestion du dépassement de `nombre_places` (voir
 * app/Services/ApplicationCapacityChecker.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();

            $table->foreignId('application_call_id')->constrained('application_calls')->restrictOnDelete();

            // Identité
            $table->string('nom');
            $table->string('prenom');
            $table->string('email');
            $table->string('telephone')->nullable();

            // Localisation
            $table->string('region')->nullable();
            $table->string('lieu')->nullable();

            // Profil / compétences / motivation (Option A)
            $table->string('tranche_age')->nullable();
            $table->string('niveau_etudes')->nullable();
            $table->string('situation_professionnelle')->nullable();
            $table->text('competences')->nullable();
            $table->text('experience')->nullable();
            $table->text('motivation')->nullable();
            $table->text('projet')->nullable();

            // Valeur contrôlée côté application (App\Enums\ApplicationStatus).
            $table->string('statut')->default('nouvelle');

            $table->timestamps();

            $table->index('statut');
            $table->index('region');
            $table->index(['application_call_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
