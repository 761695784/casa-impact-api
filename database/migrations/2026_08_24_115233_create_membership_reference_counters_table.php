<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table technique, jamais exposée par une Resource/API — même rôle que
 * `application_reference_counters` (voir ApplicationReferenceGenerator),
 * mais compteur INDÉPENDANT pour les adhésions (décision explicite du
 * 2026-08-24 : les candidatures et les adhésions ne partagent pas la même
 * séquence, même si les deux références utilisent le format
 * CI-{année}-{séquence sur 6 chiffres}). Une ligne par année.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_reference_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee')->unique();
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_reference_counters');
    }
};
