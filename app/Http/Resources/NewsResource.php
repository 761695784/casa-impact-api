<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource partagée admin/public : aucun champ sensible sur News. `media`
 * ajoutée au Module 12 (Médiathèque), sans modification du schéma `news`.
 * News n'a pas de localisation (absente de LocationController::LOCATABLE_MAP).
 *
 * @mixin \App\Models\News
 */
class NewsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'corps' => $this->corps,
            'statut' => $this->statut->value,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
