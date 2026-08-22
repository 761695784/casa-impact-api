<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `domain_id` est laissé en simple `unsignedBigInteger` nullable, SANS
 * contrainte `foreignId()->constrained()` : la migration `domains` (Module 2)
 * n'était pas fournie dans ce lot de fichiers isolé, donc rien ne garantit
 * qu'elle s'exécute avant celle-ci une fois les modules fusionnés. À durcir
 * en vraie clé étrangère (`foreignId('domain_id')->nullable()->constrained()`)
 * lors de la fusion centrale, une fois l'ordre des migrations connu.
 *
 * `region` reste un simple champ string validé côté application contre
 * `App\Enums\Region` (déjà existant, partagé — voir Module 4/15), cohérent
 * avec le choix fait pour `application_calls.region`.
 *
 * Pas de champ image/couverture ici : comme pour `news`/`programs`, la
 * couverture et les photos de parcours passent par la Médiathèque
 * polymorphique (`media`, Module 12) via HasMedia, sans colonne dédiée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talents', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('domain_id')->nullable();
            $table->string('region')->nullable();
            $table->text('presentation')->nullable();
            $table->text('parcours')->nullable();
            $table->text('projet')->nullable();
            $table->text('realisations')->nullable();
            $table->text('temoignage')->nullable();
            $table->string('recit_titre')->nullable();
            $table->text('recit_corps')->nullable();
            // Tableau JSON simple de liens externes (site web, réseaux
            // sociaux, presse...) — pas de table séparée, cohérent avec
            // `application_calls.documents_requis` (architecturev1.md §I,
            // Option A).
            $table->json('liens_externes')->nullable();
            // Valeur contrôlée côté application (App\Enums\TalentStatus).
            $table->string('statut')->default('brouillon');
            $table->timestamps();

            $table->index('statut');
            $table->index('domain_id');
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talents');
    }
};
