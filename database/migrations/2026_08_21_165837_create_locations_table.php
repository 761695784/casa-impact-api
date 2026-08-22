<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cartographie (Module 15) — Option C retenue dans architecturev1.md §G :
 * table `locations` polymorphique unique, associée à la demande à Program,
 * ApplicationCall, Talent (et toute future entité territoriale) SANS jamais
 * migrer leur propre table. Un couple (locatable_type, locatable_id) unique
 * : une entité porte au plus un point sur la carte dans cette version — à
 * revoir seulement si un vrai besoin de plusieurs points par entité émerge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('locatable_type');
            $table->unsignedBigInteger('locatable_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('libelle')->nullable();
            // Dénormalisé depuis l'entité liée pour permettre un filtrage
            // rapide côté carte publique sans jointure supplémentaire.
            $table->string('region')->nullable();
            $table->timestamps();

            $table->unique(['locatable_type', 'locatable_id']);
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
