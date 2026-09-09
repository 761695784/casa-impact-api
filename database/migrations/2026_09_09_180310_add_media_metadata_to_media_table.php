<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passage de `media` d'une table "possédée" par une seule fiche
 * (mediable_type/mediable_id NOT NULL, une photo = un usage) à une vraie
 * médiathèque réutilisable : décision validée le 2026-09-10 (remplace la
 * note d'architecturev1.md §H "Option B" écrite avant que le besoin de
 * réemploi d'une même photo sur plusieurs actualités/programmes soit
 * exprimé). Ces colonnes décrivent désormais le FICHIER lui-même,
 * indépendamment de qui l'utilise — l'usage par fiche est déplacé vers
 * `media_attachments` (voir migration suivante).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('nom')->nullable()->after('id');
            $table->string('alt')->nullable()->after('legende');
            $table->string('categorie')->default('general')->after('alt');
            $table->string('statut')->default('actif')->after('categorie');
            $table->string('dimensions')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['nom', 'alt', 'categorie', 'statut', 'dimensions']);
        });
    }
};
