<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();

            // Correspond à une clé de ApplicationCall.documents_requis
            // (ex. "cv", "piece_identite") — pas de table de types séparée,
            // simple string contrôlé côté validation (StoreApplicationRequest).
            $table->string('type');
            $table->string('chemin');
            $table->string('nom_original');
            $table->string('mime');
            $table->unsignedBigInteger('taille');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
