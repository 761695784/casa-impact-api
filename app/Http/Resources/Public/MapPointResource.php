<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représente un point sur la carte publique : coordonnées + un minimum
 * d'informations sur l'entité liée (jamais l'entité complète — la carte
 * n'a besoin que d'un titre/slug pour lier vers la fiche détaillée côté
 * frontend). Le "type" identifie l'entité liée via le même alias que
 * MediaController::MEDIABLE_MAP, pour rester cohérent dans toute l'API.
 *
 * @mixin \App\Models\Location
 */
class MapPointResource extends JsonResource
{
    public function __construct($resource, private readonly string $type)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $entity = $this->locatable;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'libelle' => $this->libelle,
            'region' => $this->region?->value,
            'titre' => $entity->titre ?? $entity->nom ?? null,
            'slug' => $entity->slug ?? null,
        ];
    }
}
