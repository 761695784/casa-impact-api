<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->string('slug')->unique();
            $table->longText('corps');
            $table->string('meta_description')->nullable();
            // Valeur contrôlée côté application (App\Enums\PageStatus), pas
            // d'ENUM SQL — cohérent avec architecturev1.md §B.
            $table->string('statut')->default('brouillon');
            $table->timestamps();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
