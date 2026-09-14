<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les adhérents importés depuis l'historique Excel (avant l'automatisation
 * du site — import du 2026-09-11) n'ont pas systématiquement de photo
 * numérisée disponible immédiatement (seulement un lien Google Drive dans
 * le fichier source, non téléchargé lors de l'import). `photo_path` doit
 * donc pouvoir rester vide pour ces membres-là, contrairement à une
 * adhésion soumise via le site (toujours requise, voir
 * StoreMembershipRequest). Le service de génération de carte
 * (MembershipCardService) et le gabarit PDF gèrent déjà proprement un
 * photo_path vide (encadré doré laissé vide) — aucun autre changement
 * nécessaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('photo_path')->nullable(false)->change();
        });
    }
};
