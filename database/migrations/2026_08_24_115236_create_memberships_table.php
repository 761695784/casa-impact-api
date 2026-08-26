<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Adhésion — champs directement dérivés du formulaire Google Forms
 * officiel ("Formulaire d'adhésion à CASA IMPACT") fourni par l'utilisateur
 * le 2026-08-24. `numero_membre` suit le même format que la référence de
 * candidature (CI-{année}-{séquence 6 chiffres}) mais via un compteur
 * indépendant — voir MembershipReferenceGenerator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->string('numero_membre')->unique();
            $table->string('nom_complet');
            $table->string('email');
            $table->string('telephone');
            $table->string('profession')->nullable();
            $table->string('region');
            // Nullable : obligatoire seulement pour les régions de
            // Casamance (Ziguinchor/Sédhiou/Kolda) — voir
            // StoreMembershipRequest::rules(), un adhérent de Dakar ou de la
            // diaspora n'a pas de département en Casamance à renseigner.
            $table->string('departement')->nullable();
            $table->string('domaine_contribution')->nullable();
            $table->string('type_contribution')->nullable();
            // Chemin de stockage direct (disque `public`), pas de passage
            // par le système Média polymorphique (App\Traits\HasMedia) :
            // contrairement à Talent/Partner, la photo est un champ requis
            // unique et intrinsèque à l'adhésion, pas une pièce jointe
            // gérée séparément — inutile d'introduire cette complexité ici.
            $table->string('photo_path');
            $table->boolean('engagement_moral')->default(false);
            $table->text('suggestions_competences')->nullable();
            // en_attente_paiement / validee / refusee — voir MembershipStatus.
            $table->string('statut')->default('en_attente_paiement');
            // site (soumission publique) / manuel (saisie admin pour les
            // adhérents antérieurs au site) — voir demande explicite du
            // 2026-08-24.
            $table->string('source')->default('site');
            $table->text('admin_note')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('statut');
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
