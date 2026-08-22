<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource partagée admin/public : aucun champ sensible. `values` n'est
 * incluse que si eager-loadée (whenLoaded) — le endpoint public charge
 * systématiquement les valeurs, l'index admin ne les charge pas par défaut
 * (évite N+1 sur une longue liste d'indicateurs en back-office).
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
            'values' => ImpactValueResource::collection($this->whenLoaded('values')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
