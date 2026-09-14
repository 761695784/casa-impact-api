<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Utilisée dans deux contextes : imbriquée sous ApplicationResource::history
 * (où `application` n'est jamais eager-loadée, donc omise — inutile de se
 * répéter soi-même) et à plat dans Admin\ApplicationController::history()
 * (page/export d'historique, accord du 2026-09-14), où `application` EST
 * eager-loadée pour identifier le candidat concerné par chaque ligne.
 * Volontairement un sous-tableau minimal plutôt qu'un ApplicationResource
 * complet (pas de documents/motivation/etc., inutiles pour une ligne
 * d'historique).
 *
 * @mixin \App\Models\ApplicationHistory
 */
class ApplicationHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'ancien_statut' => $this->ancien_statut,
            'nouveau_statut' => $this->nouveau_statut,
            'sujet_email' => $this->sujet_email,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null),
            'application' => $this->whenLoaded('application', fn () => $this->application ? [
                'id' => $this->application->id,
                'reference' => $this->application->reference,
                'candidat_nom' => trim(($this->application->prenom ?? '') . ' ' . ($this->application->nom ?? '')),
                'application_call' => $this->application->relationLoaded('applicationCall') && $this->application->applicationCall
                    ? ['id' => $this->application->applicationCall->id, 'titre' => $this->application->applicationCall->titre]
                    : null,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
