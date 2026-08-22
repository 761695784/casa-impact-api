<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource partagée admin/public : aucun champ sensible sur Talent (données
 * publiques par nature — page succès/portrait). `domain`/`media`/`location`
 * ne sont inclus que si eager-loadés en amont (whenLoaded) — jamais de
 * requête N+1 déclenchée depuis la Resource elle-même.
 *
 * @mixin \App\Models\Talent
 */
class TalentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'domain_id' => $this->domain_id,
            'domain' => new DomainResource($this->whenLoaded('domain')),
            'region' => $this->region?->value,
            'presentation' => $this->presentation,
            'parcours' => $this->parcours,
            'projet' => $this->projet,
            'realisations' => $this->realisations,
            'temoignage' => $this->temoignage,
            'recit_titre' => $this->recit_titre,
            'recit_corps' => $this->recit_corps,
            'liens_externes' => $this->liens_externes,
            'statut' => $this->statut->value,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
