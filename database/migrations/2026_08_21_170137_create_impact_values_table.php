<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impact_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impact_indicator_id')->constrained()->cascadeOnDelete();
            $table->decimal('valeur', 15, 2);
            // Chaîne libre plutôt qu'une date (ex. "2025", "2025-T3", "2025-06")
            // — la granularité temporelle diffère selon l'indicateur (annuel,
            // trimestriel...), pas de format unique imposable en V1.
            $table->string('periode');
            $table->string('region')->nullable();
            $table->timestamps();

            $table->index(['impact_indicator_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impact_values');
    }
};
