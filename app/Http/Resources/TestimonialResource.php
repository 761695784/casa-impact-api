<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource partagée admin/public : aucun champ sensible sur Testimonial.
 * `program`/`applicationCall` ne sont inclus que si eager-loadés en amont
 * (whenLoaded) — jamais de requête N+1 déclenchée depuis la Resource
 * elle-même.
 *
 * @mixin \App\Models\Testimonial
 */
class TestimonialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'auteur' => $this->auteur,
            'role_organisation' => $this->role_organisation,
            'citation' => $this->citation,
            'contexte' => $this->contexte,
            'program_id' => $this->program_id,
            'application_call_id' => $this->application_call_id,
            'program' => new ProgramResource($this->whenLoaded('program')),
            'application_call' => new ApplicationCallResource($this->whenLoaded('applicationCall')),
            'statut' => $this->statut->value,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
