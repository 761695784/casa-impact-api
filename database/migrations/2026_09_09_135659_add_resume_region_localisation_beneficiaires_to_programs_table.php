<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Décision produit validée le 2026-09-09 : le formulaire admin Programme
 * existant (résumé d'accroche, région, précision de localisation, nombre de
 * bénéficiaires accompagnés) est conservé tel quel plutôt que retiré — ces
 * 4 colonnes rejoignent donc officiellement le schéma `programs`, en
 * complément de `App\Traits\HasLocation` (géolocalisation précise, ajoutée
 * au Module 15) : `region`/`localisation` restent une indication texte
 * légère au niveau du programme, indépendante de tout point cartographique
 * éventuellement rattaché.
 *
 * `region` n'est pas une colonne ENUM SQL — cohérent avec `statut` sur la
 * même table et avec `App\Enums\Region` (déjà utilisé par le Module 4,
 * limité aux 3 régions de la zone d'intervention).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('resume')->nullable()->after('description');
            $table->string('region')->nullable()->after('resume');
            $table->string('localisation')->nullable()->after('region');
            $table->unsignedInteger('beneficiaires_count')->nullable()->after('date_fin');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['resume', 'region', 'localisation', 'beneficiaires_count']);
        });
    }
};
