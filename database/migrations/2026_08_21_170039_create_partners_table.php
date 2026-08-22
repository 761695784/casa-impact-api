<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 11 (Partenaires). Volontairement PAS de colonne `slug` (pas de page
 * de détail individuelle) ni `logo_url` (logo géré exclusivement via la
 * Médiathèque polymorphique `media`, collection `'logo'`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('lien')->nullable();
            // Colonne contrôlée côté application (App\Enums\PartnerType).
            $table->string('type')->nullable();
            // Colonne contrôlée côté application (App\Enums\PartnerStatus) —
            // "actif/inactif", pas "publié/brouillon".
            $table->string('statut')->default('actif');
            // Ordre d'affichage manuel, géré par l'équipe communication.
            $table->tinyInteger('ordre')->default(0);
            $table->timestamps();

            $table->index('statut');
            $table->index('ordre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
