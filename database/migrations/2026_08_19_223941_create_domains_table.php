<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les 6 domaines d'intervention sont un référentiel fixe défini par le
 * brief métier (décision validée : verrouillés, seedés, update uniquement —
 * pas de création/suppression via l'API, voir DomainPolicy). Le `slug` n'est
 * volontairement PAS modifiable après le seed : il sera référencé par
 * `Program` (module suivant) et par l'URL publique — le changer casserait
 * des liens existants.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icone')->nullable();
            $table->unsignedTinyInteger('ordre')->default(0);
            $table->string('statut')->default('actif');
            $table->timestamps();

            $table->index('statut');
            $table->index('ordre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
