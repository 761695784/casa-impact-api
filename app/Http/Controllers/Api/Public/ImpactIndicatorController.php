<?php

namespace App\Http\Controllers\Api\Public;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Resources\ImpactIndicatorResource;
use App\Models\ImpactIndicator;

/**
 * Lecture seule, aucune authentification. Les indicateurs d'impact n'ont
 * pas de cycle brouillon/publié (voir ImpactIndicator) : tout indicateur
 * créé par l'administration est visible ici, avec ses valeurs chargées.
 */
#[Group('Impact — Public')]
class ImpactIndicatorController extends Controller
{
    public function index()
    {
        $indicators = ImpactIndicator::query()
            ->with('values')
            ->orderBy('libelle')
            ->get();

        return ImpactIndicatorResource::collection($indicators);
    }
}
