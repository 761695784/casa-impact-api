<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lien WhatsApp à usage unique (accord du 2026-09-18) : le lien brut du
 * groupe WhatsApp envoyé dans l'email de validation (MembershipValidated)
 * pouvait être libremment repartagé par un adhérent à des personnes non
 * enregistrées en base. Chaque adhésion reçoit désormais un jeton unique
 * (`whatsapp_invite_token`) généré à la volée au moment de l'envoi de
 * l'email (voir Membership::ensureWhatsappInviteToken()) ; l'email pointe
 * vers une route de redirection (`/rejoindre-whatsapp/{token}` — voir
 * WhatsappInviteController) qui redirige UNE SEULE fois vers le vrai lien
 * WhatsApp puis marque `whatsapp_invite_used_at`, bloquant tout clic
 * ultérieur sur ce même lien (repartagé ou non).
 *
 * Limite assumée et à garder en tête : une fois le clic effectué et le
 * groupe WhatsApp ouvert, rien n'empêche techniquement la personne de
 * repartager le lien RÉEL du groupe une fois qu'elle l'a sous les yeux
 * dans l'app WhatsApp — WhatsApp ne propose pas d'invitation nominative à
 * usage unique côté serveur. Ce mécanisme bloque en revanche totalement le
 * partage AVANT le premier clic (le cas le plus probable en pratique : un
 * transfert de l'email ou du lien lui-même), ce qui couvre l'essentiel du
 * risque décrit par l'utilisateur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('whatsapp_invite_token', 36)->nullable()->unique()->after('validated_by');
            $table->timestamp('whatsapp_invite_used_at')->nullable()->after('whatsapp_invite_token');
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_invite_token', 'whatsapp_invite_used_at']);
        });
    }
};
