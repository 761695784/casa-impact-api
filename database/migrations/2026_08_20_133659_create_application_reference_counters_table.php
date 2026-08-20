<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table technique, jamais exposée par une Resource/API — sert uniquement de
 * verrou pour générer une référence de candidature atomique et sans
 * collision (format CI-{année}-{séquence sur 6 chiffres}), voir
 * app/Services/ApplicationReferenceGenerator.php. Une ligne par année.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_reference_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee')->unique();
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_reference_counters');
    }
};
