<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table de liaison polymorphique N-N entre `media` (le fichier, réutilisable)
 * et n'importe quelle fiche consommatrice (News, Program, Talent, Partner,
 * ApplicationCall, Testimonial — voir App\Traits\HasMedia et
 * MediaController::MEDIABLE_MAP). Remplace la relation 1-N directe
 * (mediable_type/mediable_id portés par `media` lui-même) : une même photo
 * peut désormais illustrer plusieurs fiches à la fois, chacune avec sa
 * propre `collection` (cover/gallery/logo/...) et son propre `ordre`
 * d'affichage — voir HasMedia::media() (morphToMany).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('mediable_type');
            $table->unsignedBigInteger('mediable_id');
            $table->string('collection')->default('gallery');
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id']);
            $table->index(['mediable_type', 'mediable_id', 'collection']);
            // Empêche d'attacher deux fois la même photo à la même fiche
            // dans la même collection (double-clic, retry réseau...).
            $table->unique(['media_id', 'mediable_type', 'mediable_id', 'collection'], 'media_attachments_unique_link');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_attachments');
    }
};
