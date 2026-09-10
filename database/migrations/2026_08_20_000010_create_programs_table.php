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
            $table->string('resume')->nullable();
            $table->string('region')->nullable();
            $table->string('localisation')->nullable();
            $table->string('statut')->default('brouillon');
            $table->foreignId('domain_id')->constrained('domains')->restrictOnDelete();
            $table->foreignId('program_type_id')->constrained('program_types')->restrictOnDelete();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->unsignedInteger('beneficiaires_count')->nullable();
            $table->timestamps();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
