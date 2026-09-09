<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * À utiliser sur tout modèle consommant la Médiathèque (Program, News,
 * Talent, Partner, ApplicationCall, Testimonial — architecturev1.md §H).
 *
 * Refonte du 2026-09-10 : `media()` passe de morphMany (une photo = une
 * seule fiche propriétaire) à morphToMany via la table pivot
 * `media_attachments` — une même photo de la médiathèque peut désormais
 * illustrer plusieurs fiches à la fois. Signature strictement compatible
 * avec l'ancienne relation (`$model->media`, `->load('media')`,
 * `->with('media')`, `MediaResource::collection($this->whenLoaded('media'))`
 * continuent de fonctionner sans changement dans les contrôleurs/resources
 * existants) : seule cette méthode change.
 */
trait HasMedia
{
    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'media_attachments')
            ->withPivot(['collection', 'ordre'])
            ->withTimestamps()
            ->orderBy('media_attachments.ordre');
    }

    public function mediaInCollection(string $collection)
    {
        return $this->media()->wherePivot('collection', $collection)->get();
    }
}
