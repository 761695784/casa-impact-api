<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration de données : toute ligne `media` existante avait un unique
 * propriétaire (mediable_type/mediable_id/collection/ordre portés
 * directement par la ligne). On recrée l'attachement correspondant dans
 * `media_attachments` avant de supprimer ces colonnes de `media` — sans
 * cette étape, les photos déjà uploadées (via l'ancien flux 1-1) se
 * retrouveraient orphelines de toute fiche après la migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('media', 'mediable_type')) {
            $now = now();

            DB::table('media')
                ->select('id', 'mediable_type', 'mediable_id', 'collection', 'ordre')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($now) {
                    $attachments = $rows->map(fn ($row) => [
                        'media_id' => $row->id,
                        'mediable_type' => $row->mediable_type,
                        'mediable_id' => $row->mediable_id,
                        'collection' => $row->collection ?: 'gallery',
                        'ordre' => $row->ordre ?: 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    if (! empty($attachments)) {
                        DB::table('media_attachments')->insertOrIgnore($attachments);
                    }
                });

            Schema::table('media', function (Blueprint $table) {
                $table->dropColumn(['mediable_type', 'mediable_id', 'collection', 'ordre']);
            });
        }
    }

    /**
     * Reconstruction best-effort : réattribue chaque média au PREMIER
     * attachement trouvé (id le plus bas). Avec le nouveau modèle
     * réutilisable, un média peut avoir plusieurs attachements — cette
     * information serait perdue en cas de rollback, mais `media_attachments`
     * elle-même n'est pas supprimée par ce down() (voir la migration
     * précédente pour son propre rollback).
     */
    public function down(): void
    {
        if (! Schema::hasColumn('media', 'mediable_type')) {
            Schema::table('media', function (Blueprint $table) {
                $table->string('mediable_type')->nullable()->after('id');
                $table->unsignedBigInteger('mediable_id')->nullable()->after('mediable_type');
                $table->string('collection')->default('default')->after('mediable_id');
                $table->unsignedInteger('ordre')->default(0)->after('legende');
            });

            DB::table('media_attachments')
                ->select('media_id', 'mediable_type', 'mediable_id', 'collection', 'ordre')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('media')
                            ->where('id', $row->media_id)
                            ->whereNull('mediable_type')
                            ->update([
                                'mediable_type' => $row->mediable_type,
                                'mediable_id' => $row->mediable_id,
                                'collection' => $row->collection,
                                'ordre' => $row->ordre,
                            ]);
                    }
                }, 'media_id');
        }
    }
};
