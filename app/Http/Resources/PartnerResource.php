<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource partagée admin/public : aucun champ sensible sur Partner. Le logo
 * n'est PAS un champ direct — il faut le récupérer dans `media` (collection
 * `'logo'`, voir Partner::mediaInCollection('logo')), d'où l'inclusion de
 * `media` ici plutôt qu'un champ `logo_url` inexistant en base.
 *
 * @mixin \App\Models\Partner
 */
class PartnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'lien' => $this->lien,
            'type' => $this->type?->value,
            'statut' => $this->statut?->value,
            'ordre' => $this->ordre,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
