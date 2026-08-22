<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Stocké sur le disque `public` (contrairement aux documents de candidature,
 * privés — voir Module 5) : ces fichiers sont, par nature, destinés à être
 * affichés publiquement (couverture d'actualité, logo de partenaire...).
 * Nécessite `php artisan storage:link` chez toi.
 *
 * @mixin \App\Models\Media
 */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'collection' => $this->collection,
            'url' => Storage::disk('public')->url($this->fichier),
            'nom_original' => $this->nom_original,
            'mime' => $this->mime,
            'taille' => $this->taille,
            'legende' => $this->legende,
            'ordre' => $this->ordre,
        ];
    }
}
