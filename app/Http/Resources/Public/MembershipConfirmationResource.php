<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Réponse à la soumission publique du formulaire d'adhésion — volontairement
 * minimale, même principe que ApplicationConfirmationResource : seuls le
 * numéro de dossier et le statut initial sont renvoyés, jamais une
 * MembershipResource complète (données personnelles, voir
 * Admin\MembershipResource).
 *
 * @mixin \App\Models\Membership
 */
class MembershipConfirmationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'numero_membre' => $this->numero_membre,
            'statut' => $this->statut->value,
        ];
    }
}
