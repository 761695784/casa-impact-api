<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des candidatures (accord du 2026-09-14 : "on aura besoin plus
 * tard de tous les historiques après pour présenter les partenaires ce qui
 * s'est fait") — une ligne par événement, de deux types :
 *   - `statut_change` : chaque fois que le statut d'une candidature change
 *     (via Admin\ApplicationController::update()), qu'un email soit envoyé
 *     ou non.
 *   - `email_envoye` : chaque fois qu'un email contextuel est réellement
 *     envoyé au candidat — que ce soit automatiquement (passage à un statut
 *     de décision) ou manuellement (bouton "Envoyer un email" ajouté à
 *     Admin\ApplicationController::notify()).
 *
 * Volontairement une table à part plutôt que des colonnes sur `applications`
 * : un historique est par nature une liste d'événements (plusieurs emails
 * peuvent être envoyés pour un même statut, ex. un renvoi manuel), pas un
 * état unique à écraser.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'statut_change' | 'email_envoye'
            $table->string('ancien_statut')->nullable();
            $table->string('nouveau_statut')->nullable();
            $table->string('sujet_email')->nullable();
            // nullable + nullOnDelete : l'historique doit survivre à la
            // suppression du compte de l'admin qui a agi (traçabilité pour
            // les partenaires), pas à la suppression de la candidature
            // elle-même (cascadeOnDelete ci-dessus, cohérent avec
            // ApplicationDocument).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_history');
    }
};
