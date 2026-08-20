<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `region` reste un simple champ string validé côté application contre une
 * liste fixe (ziguinchor/kolda/sedhiou) — décision validée le 2026-08-20 :
 * pas de table Region/Department/Commune pour l'instant (marquée [DÉCISION
 * REQUISE] dans architecturev1.md §C, non construite), à reconstruire en
 * référentiel dédié seulement si un futur module (Cartographie, Contact) a
 * réellement besoin de plus de granularité territoriale.
 *
 * `nombre_places` est stocké ici mais SANS aucune logique d'enforcement
 * (places épuisées, liste d'attente...) : cette décision, marquée
 * "bloquante" dans architecturev1.md §I, ne s'applique qu'au moment où une
 * candidature (`Application`) est soumise — Module 7, pas celui-ci.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_calls', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->text('objectifs')->nullable();
            $table->text('public_cible')->nullable();
            $table->string('region')->nullable();
            $table->string('lieu')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            // Date limite de dépôt des candidatures (distincte des dates de
            // l'activité elle-même).
            $table->date('date_limite')->nullable();
            // Texte libre ("3 jours", "6 mois"...) : trop variable pour un
            // champ structuré, cohérent avec l'anti-sur-ingénierie du projet.
            $table->string('duree')->nullable();
            $table->unsignedInteger('nombre_places')->nullable();
            $table->text('conditions')->nullable();
            // Tableau JSON simple de types de documents requis (ex. ["cv",
            // "piece_identite"]) — pas de table EAV séparée, cohérent avec
            // l'option retenue pour les champs dynamiques (architecturev1.md
            // §I, Option A).
            $table->json('documents_requis')->nullable();
            // Valeur contrôlée côté application (App\Enums\ApplicationCallStatus).
            $table->string('statut')->default('brouillon');

            $table->foreignId('program_id')->constrained('programs')->restrictOnDelete();

            $table->timestamps();

            $table->index('statut');
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_calls');
    }
};
