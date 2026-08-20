<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            // Valeur contrôlée côté application (App\Enums\ProgramStatus),
            // pas d'ENUM SQL — cohérent avec architecturev1.md §B.
            $table->string('statut')->default('brouillon');

            // Domaine verrouillé (Module 2) : jamais supprimé via l'API,
            // mais on garde restrictOnDelete en défense en profondeur au
            // niveau DB plutôt que de faire confiance uniquement à l'absence
            // de route DELETE.
            $table->foreignId('domain_id')->constrained('domains')->restrictOnDelete();

            // ProgramType EST supprimable via l'admin (table éditable) :
            // restrictOnDelete empêche la suppression d'un type encore
            // utilisé par un programme — ProgramTypeController::destroy()
            // vérifie aussi explicitement en amont pour renvoyer un 409
            // JSON propre plutôt que de laisser remonter l'exception SQL.
            $table->foreignId('program_type_id')->constrained('program_types')->restrictOnDelete();

            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->timestamps();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
