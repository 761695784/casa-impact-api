<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Décision produit validée le 2026-09-09 : l'admin Types de programme gère
 * les types via un statut actif/inactif (au lieu d'une suppression
 * définitive) et un ordre d'affichage manuel — comportement conservé, donc
 * ces 2 colonnes rejoignent le schéma `program_types`.
 *
 * Réutilise `App\Enums\DomainStatus` (déjà `actif`/`inactif` sur Domain,
 * Module 2) plutôt que d'introduire un 2ᵉ enum de statut équivalent.
 * Le DELETE réel (`ProgramTypeController::destroy()`, avec 409 si le type
 * est encore utilisé) reste disponible en parallèle — statut inactif et
 * suppression définitive sont deux actions distinctes, pas l'une à la
 * place de l'autre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_types', function (Blueprint $table) {
            $table->string('statut')->default('actif')->after('description');
            $table->unsignedInteger('ordre')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('program_types', function (Blueprint $table) {
            $table->dropColumn(['statut', 'ordre']);
        });
    }
};
