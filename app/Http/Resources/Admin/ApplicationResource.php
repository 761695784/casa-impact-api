<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApplicationCallResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dans App\Http\Resources\Admin\ (comme UserResource) — PAS de variante
 * publique : une candidature contient des données personnelles (identité,
 * motivation...) qui ne doivent jamais être exposées via /api/public/*
 * (architecturev1.md §C).
 *
 * @mixin \App\Models\Application
 */
class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'statut' => $this->statut->value,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'region' => $this->region?->value,
            'lieu' => $this->lieu,
            'tranche_age' => $this->tranche_age,
            'niveau_etudes' => $this->niveau_etudes,
            'situation_professionnelle' => $this->situation_professionnelle,
            'competences' => $this->competences,
            'experience' => $this->experience,
            'motivation' => $this->motivation,
            'projet' => $this->projet,
            'application_call_id' => $this->application_call_id,
            'application_call' => new ApplicationCallResource($this->whenLoaded('applicationCall')),
            'documents' => ApplicationDocumentResource::collection($this->whenLoaded('documents')),
            // Historique des changements de statut + emails envoyés (accord
            // du 2026-09-14) — uniquement si eager-loadée (voir
            // Admin\ApplicationController::show()/update()/notify()).
            'history' => ApplicationHistoryResource::collection($this->whenLoaded('history')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
