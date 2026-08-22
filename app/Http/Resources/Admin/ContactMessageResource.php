<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dans App\Http\Resources\Admin\ (comme UserResource/Admin\ApplicationResource)
 * — PAS de variante publique : un message de contact contient des données
 * personnelles (identité, email, téléphone de l'expéditeur) qui ne doivent
 * jamais être exposées via /api/public/*. Le point d'entrée public
 * (Public\ContactMessageController::store()) ne renvoie qu'un message de
 * confirmation, jamais cette Resource.
 *
 * @mixin \App\Models\ContactMessage
 */
class ContactMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'categorie' => $this->categorie->value,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'sujet' => $this->sujet,
            'message' => $this->message,
            'statut' => $this->statut->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
