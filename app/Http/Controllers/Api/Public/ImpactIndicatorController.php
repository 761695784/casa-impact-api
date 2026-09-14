<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImpactIndicatorResource;
use App\Models\ImpactIndicator;

/**
 * Lecture publique des indicateurs d'impact (page /impact du site public),
 * aucune authentification requise. Tous les indicateurs sont renvoyés — pas
 * de notion de brouillon pour ce modèle (voir ImpactIndicator).
 */
class ImpactIndicatorController extends Controller
{
    public function index()
    {
        $indicators = ImpactIndicator::query()->with('values')->orderBy('id')->get();

        return ImpactIndicatorResource::collection($indicators);
    }
}
