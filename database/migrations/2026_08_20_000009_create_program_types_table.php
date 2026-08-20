<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `ProgramType` (formation, masterclass, caravane, incubation...) est
 * marqué [DÉCISION REQUISE] dans architecturev1.md §C. Décision validée le
 * 2026-08-19 : table dédiée ET ÉDITABLE (contrairement à Domain) — le brief
 * ne fournit aucune liste officielle et fermée de types, donc CRUD complet
 * côté admin (voir ProgramTypeController, ProgramTypePolicy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_types', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_types');
    }
};
