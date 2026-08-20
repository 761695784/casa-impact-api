<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource partagée admin/public : aucun champ sensible sur Page, pas besoin
 * de deux classes distinctes (contrairement à User). Les contrôleurs publics
 * filtrent déjà en amont (scopePublished) — cette Resource se contente de
 * sérialiser ce qui a été explicitement récupéré.
 *
 * @mixin \App\Models\Page
 */
class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'slug' => $this->slug,
            'corps' => $this->corps,
            'meta_description' => $this->meta_description,
            'statut' => $this->statut->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
