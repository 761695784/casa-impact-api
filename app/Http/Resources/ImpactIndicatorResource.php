<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une seule Resource partagée admin/public (pas de dossier Admin/ ni Public/,
 * contrairement à MembershipResource) : un indicateur d'impact n'a aucune
 * donnée sensible et est identique dans les deux contextes — la distinction
 * se fait uniquement sur QUELS indicateurs sont renvoyés (tous côté admin,
 * voir Api\Admin\ImpactIndicatorController::index()), jamais sur leur forme.
 *
 * @mixin \App\Models\ImpactIndicator
 */
class ImpactIndicatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'unite' => $this->unite,
            'description' => $this->description,
            'values' => ImpactValueResource::collection($this->values),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
