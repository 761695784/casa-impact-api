<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Volontairement pas de `chemin` (chemin de stockage disque) exposé — seul
 * un lien vers l'action de téléchargement dédiée (route protégée) sera
 * pertinent côté frontend, jamais le chemin serveur brut.
 *
 * @mixin \App\Models\ApplicationDocument
 */
class ApplicationDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'nom_original' => $this->nom_original,
            'mime' => $this->mime,
            'taille' => $this->taille,
            'created_at' => $this->created_at,
        ];
    }
}
