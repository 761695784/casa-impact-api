<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pas de colonne `slug` : les témoignages sont de courtes citations, jamais
 * exposées sur une page de détail dédiée (voir Testimonial::generateUniqueSlug
 * — volontairement absent, et Api\Public\TestimonialController — pas de
 * route show()).
 *
 * `program_id`/`application_call_id` restent en `unsignedBigInteger` nullable
 * SANS contrainte `foreignId()->constrained()`, pour la même raison que
 * `talents.domain_id` : les migrations `programs`/`application_calls`
 * (Modules 3/4) ne sont pas visibles dans ce lot isolé. À durcir en vraies
 * clés étrangères lors de la fusion centrale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('auteur');
            $table->string('role_organisation')->nullable();
            $table->text('citation');
            $table->text('contexte')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('application_call_id')->nullable();
            // Valeur contrôlée côté application (App\Enums\TestimonialStatus).
            $table->string('statut')->default('brouillon');
            $table->timestamps();

            $table->index('statut');
            $table->index('program_id');
            $table->index('application_call_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
