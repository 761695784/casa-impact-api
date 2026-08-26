<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dans App\Http\Resources\Admin\, comme ApplicationResource : une adhésion
 * contient des données personnelles (identité, contact, photo), jamais de
 * variante publique.
 *
 * @mixin \App\Models\Membership
 */
class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_membre' => $this->numero_membre,
            'statut' => $this->statut->value,
            'nom_complet' => $this->nom_complet,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'profession' => $this->profession,
            'region' => $this->region?->value,
            'departement' => $this->departement,
            'domaine_contribution' => $this->domaine_contribution?->value,
            'type_contribution' => $this->type_contribution?->value,
            'photo_url' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
            'engagement_moral' => $this->engagement_moral,
            'suggestions_competences' => $this->suggestions_competences,
            'source' => $this->source,
            'admin_note' => $this->admin_note,
            'validated_at' => $this->validated_at,
            'validated_by' => $this->validated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
