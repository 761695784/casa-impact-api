<?php

namespace App\Services\Cartography;

use App\Models\ApplicationCall;
use App\Models\Program;
use App\Models\Talent;

/**
 * Construit la liste unifiée des points de la Cartographie Territoriale
 * (MapPointResource côté frontend, voir types/models.ts) en agrégeant :
 *
 *   - les Programmes actifs/publiés,
 *   - les Appels à candidatures,
 *   - les Talents,
 *   - 3 points fixes "implantation" (antennes régionales Casa Impact) —
 *     accord du 2026-09-11 : pas encore gérables depuis l'admin, codés en
 *     dur ici ; pour en faire une vraie ressource CRUD plus tard, il
 *     suffira de remplacer implantations() par une requête sur un modèle
 *     Implantation.
 *
 * Aucun de ces modèles n'a de latitude/longitude en base : la position de
 * chaque point est déduite de sa région + son texte de localisation via
 * CasamanceLocations::resolve() (voir cette classe pour le détail).
 *
 * Utilisée à la fois par Api\Admin\CartographyController (tout, y compris
 * brouillons — vue de gestion) et Api\Public\CartographyController (filtré
 * aux éléments publiés — vue publique de la carte).
 */
class CartographyAggregator
{
    public function collect(bool $publicOnly): array
    {
        $points = [];
        $id = 1;

        foreach ($this->programs($publicOnly) as $program) {
            $loc = CasamanceLocations::resolve($program->region?->value ?? $program->region, $program->localisation, $id);

            $points[] = [
                'id' => $id++,
                'type' => 'program',
                'latitude' => $loc['lat'],
                'longitude' => $loc['lng'],
                'libelle' => $program->titre,
                'region' => $program->region?->value ?? $program->region,
                'statut' => $program->statut?->value ?? $program->statut,
                'titre' => $program->titre,
                'slug' => $program->slug,
                'departement' => $loc['departement'],
                'commune' => $loc['commune'],
                'description' => $program->resume ?? $program->description,
                'domaine_nom' => $program->domain?->nom,
            ];
        }

        foreach ($this->applicationCalls($publicOnly) as $call) {
            $loc = CasamanceLocations::resolve($call->region?->value ?? $call->region, $call->lieu, $id);

            $points[] = [
                'id' => $id++,
                'type' => 'application-call',
                'latitude' => $loc['lat'],
                'longitude' => $loc['lng'],
                'libelle' => $call->titre,
                'region' => $call->region?->value ?? $call->region,
                'statut' => $call->statut?->value ?? $call->statut,
                'titre' => $call->titre,
                'slug' => $call->slug,
                'departement' => $loc['departement'],
                'commune' => $loc['commune'],
                'description' => $call->resume ?? $call->description,
                'domaine_nom' => $call->program?->domain?->nom,
            ];
        }

        foreach ($this->talents($publicOnly) as $talent) {
            $loc = CasamanceLocations::resolve($talent->region?->value ?? $talent->region, $talent->localisation, $id);

            $points[] = [
                'id' => $id++,
                'type' => 'talent',
                'latitude' => $loc['lat'],
                'longitude' => $loc['lng'],
                'libelle' => $talent->nom,
                'region' => $talent->region?->value ?? $talent->region,
                'statut' => $talent->statut?->value ?? $talent->statut,
                'titre' => $talent->nom,
                'slug' => $talent->slug,
                'departement' => $loc['departement'],
                'commune' => $loc['commune'],
                'description' => $talent->bio ?? $talent->presentation,
                'domaine_nom' => $talent->domain?->nom,
            ];
        }

        foreach ($this->implantations() as $implantation) {
            $points[] = [
                'id' => $id++,
                ...$implantation,
            ];
        }

        return $points;
    }

    private function programs(bool $publicOnly)
    {
        return Program::query()
            ->with('domain')
            ->when($publicOnly, fn ($q) => $q->where('statut', 'publie'))
            ->get();
    }

    private function applicationCalls(bool $publicOnly)
    {
        return ApplicationCall::query()
            ->with('program.domain')
            ->when($publicOnly, fn ($q) => $q->whereIn('statut', ['publie', 'ferme']))
            ->get();
    }

    private function talents(bool $publicOnly)
    {
        return Talent::query()
            ->with('domain')
            ->when($publicOnly, fn ($q) => $q->where('statut', 'publie'))
            ->get();
    }

    /**
     * 3 antennes régionales fixes — accord du 2026-09-11 (voir docblock de
     * classe). Coordonnées = centres de région (CasamanceLocations).
     */
    private function implantations(): array
    {
        $libelles = [
            'ziguinchor' => 'Antenne Casa Impact — Ziguinchor (siège)',
            'kolda' => 'Antenne Casa Impact — Kolda',
            'sedhiou' => 'Antenne Casa Impact — Sédhiou',
        ];

        $result = [];

        foreach ($libelles as $region => $libelle) {
            $centroid = CasamanceLocations::REGION_CENTROIDS[$region];

            $result[] = [
                'type' => 'implantation',
                'latitude' => $centroid['lat'],
                'longitude' => $centroid['lng'],
                'libelle' => $libelle,
                'region' => $region,
                'statut' => 'actif',
                'titre' => $libelle,
                'slug' => null,
                'departement' => $centroid['departement'],
                'commune' => $centroid['commune'],
                'description' => 'Point de rattachement territorial Casa Impact.',
                'domaine_nom' => null,
            ];
        }

        return $result;
    }
}
