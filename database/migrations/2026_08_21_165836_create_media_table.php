<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Médiathèque (Module 12) — table polymorphique unique et faite maison
 * (décision validée dans architecturev1.md §H : Option B, pas de
 * spatie/laravel-medialibrary). `mediable_type`/`mediable_id` permettent à
 * N'IMPORTE QUELLE entité de consommer des médias sans migration
 * supplémentaire sur sa propre table — voir App\Traits\HasMedia.
 * `collection` (ex. "cover", "gallery", "logo", "document") évite le
 * fourre-tout au sein d'une même entité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('mediable_type');
            $table->unsignedBigInteger('mediable_id');
            $table->string('collection')->default('default');
            $table->string('fichier');
            $table->string('nom_original');
            $table->string('mime');
            $table->unsignedBigInteger('taille');
            $table->string('legende')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id']);
            $table->index(['mediable_type', 'mediable_id', 'collection']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
