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
 * `collection`/`ordre` ne sont plus des colonnes de `media` (voir migration
 * du 2026-09-10) mais des attributs du pivot `media_attachments` — présents
 * uniquement quand cette Resource est rendue à travers la relation
 * `HasMedia::media()` d'une fiche précise (ex. `NewsResource` via
 * `$news->media`), absents sur un item renvoyé brut par la médiathèque
 * (GET /api/admin/media, qui liste des fichiers indépendamment de leur
 * usage).
 *
 * @mixin \App\Models\Media
 */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'url' => Storage::disk('public')->url($this->fichier),
            'nom_original' => $this->nom_original,
            'mime' => $this->mime,
            'type' => $this->type(),
            'taille' => $this->taille,
            'dimensions' => $this->dimensions,
            'alt' => $this->alt,
            'legende' => $this->legende,
            'categorie' => $this->categorie?->value,
            'statut' => $this->statut?->value,
            // Présents uniquement quand $this->pivot existe (relation
            // morphToMany HasMedia::media() — voir note ci-dessus).
            'collection' => $this->whenPivotLoaded('media_attachments', fn () => $this->pivot->collection),
            'ordre' => $this->whenPivotLoaded('media_attachments', fn () => $this->pivot->ordre),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
