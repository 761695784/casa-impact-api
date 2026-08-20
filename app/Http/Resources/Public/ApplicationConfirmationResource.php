<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Réponse à la soumission publique — volontairement minimale : seuls la
 * référence et le statut initial sont renvoyés au candidat, jamais une
 * ApplicationResource complète (voir Admin\ApplicationResource, jamais
 * exposée publiquement).
 *
 * @mixin \App\Models\Application
 */
class ApplicationConfirmationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'statut' => $this->statut->value,
        ];
    }
}
