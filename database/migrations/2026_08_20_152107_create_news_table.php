<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pas de champ image/couverture ici : la Médiathèque (Module 12, table
 * `media` polymorphique faite maison) n'existe pas encore — la couverture
 * sera rattachée à `News` via cette relation polymorphique le moment venu,
 * sans migration supplémentaire sur cette table (architecturev1.md §H).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->string('slug')->unique();
            // Colonne contrôlée côté application (App\Enums\NewsType), pas
            // de table séparée — 4 valeurs fixes selon le brief.
            $table->string('type');
            $table->longText('corps');
            // Valeur contrôlée côté application (App\Enums\NewsStatus) — 4
            // statuts (brouillon/previsualisation/publie/archive), contrairement
            // aux 3 habituels sur les autres modules de contenu.
            $table->string('statut')->default('brouillon');
            $table->timestamps();

            $table->index('statut');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
