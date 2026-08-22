<?php

namespace App\Traits;

use App\Models\Media;

/**
 * À utiliser sur tout modèle consommant la Médiathèque (Program, News,
 * Talent, Partner, ApplicationCall, Testimonial — architecturev1.md §H).
 * Aucune migration nécessaire sur le modèle qui l'utilise : la relation
 * passe entièrement par `media.mediable_type`/`mediable_id`.
 */
trait HasMedia
{
    public function media()
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('ordre');
    }

    public function mediaInCollection(string $collection)
    {
        return $this->media()->where('collection', $collection)->get();
    }
}
