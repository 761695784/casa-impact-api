<?php

namespace App\Traits;

use App\Models\Location;

/**
 * À utiliser sur tout modèle géolocalisable (Program, ApplicationCall,
 * Talent — architecturev1.md §G, Option C). Aucune migration nécessaire sur
 * le modèle qui l'utilise.
 */
trait HasLocation
{
    public function location()
    {
        return $this->morphOne(Location::class, 'locatable');
    }
}
