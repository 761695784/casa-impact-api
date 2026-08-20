<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource partagée admin/public : un appel à candidatures n'expose aucune
 * donnée personnelle (contrairement à une future ApplicationResource, qui
 * n'existera jamais côté public — voir architecturev1.md §C). La relation
 * `program` n'est incluse que si eager-loadée (whenLoaded).
 *
 * @mixin \App\Models\ApplicationCall
 */
class ApplicationCallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'slug' => $this->slug,
            'description' => $this->description,
            'objectifs' => $this->objectifs,
            'public_cible' => $this->public_cible,
            'region' => $this->region?->value,
            'lieu' => $this->lieu,
            'date_debut' => $this->date_debut?->toDateString(),
            'date_fin' => $this->date_fin?->toDateString(),
            'date_limite' => $this->date_limite?->toDateString(),
            'duree' => $this->duree,
            'nombre_places' => $this->nombre_places,
            'conditions' => $this->conditions,
            'documents_requis' => $this->documents_requis,
            'statut' => $this->statut->value,
            'program_id' => $this->program_id,
            'program' => new ProgramResource($this->whenLoaded('program')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
