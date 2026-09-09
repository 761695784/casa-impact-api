<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource partagée admin/public : aucun champ sensible sur ProgramType.
 *
 * @mixin \App\Models\ProgramType
 */
class ProgramTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'description' => $this->description,
            'statut' => $this->statut?->value,
            'ordre' => $this->ordre,
            // Présent uniquement quand la requête amont a fait
            // ->withCount('programs') (voir ProgramTypeController).
            'programmes_count' => $this->whenCounted('programs'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
