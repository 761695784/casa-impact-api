<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource partagée admin/public : aucun champ sensible sur Program. Les
 * relations `domain`/`programType` ne sont incluses que si elles ont été
 * eager-loadées en amont (whenLoaded) — jamais de requête N+1 déclenchée
 * depuis la Resource elle-même. `media`/`location` suivent la même
 * logique, ajoutées au Module 12/15 (Médiathèque/Cartographie) sans
 * modification du schéma `programs`.
 *
 * @mixin \App\Models\Program
 */
class ProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'slug' => $this->slug,
            'description' => $this->description,
            'statut' => $this->statut->value,
            'domain_id' => $this->domain_id,
            'program_type_id' => $this->program_type_id,
            'domain' => new DomainResource($this->whenLoaded('domain')),
            'program_type' => new ProgramTypeResource($this->whenLoaded('programType')),
            'date_debut' => $this->date_debut?->toDateString(),
            'date_fin' => $this->date_fin?->toDateString(),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
