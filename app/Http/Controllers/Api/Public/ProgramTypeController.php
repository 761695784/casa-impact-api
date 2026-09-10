<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProgramTypeResource;
use App\Models\ProgramType;

/**
 * Liste simple, sans authentification — sert notamment à peupler un filtre
 * côté frontend (ex. filtrer /api/public/programs?type=formation). Aucun
 * champ sensible sur ProgramType, pas de show() dédié (pas de besoin
 * identifié d'une fiche "type de programme" publique autonome).
 */
class ProgramTypeController extends Controller
{
    public function index()
    {
        return ProgramTypeResource::collection(
            ProgramType::query()->orderBy('nom')->get()
        );
    }
}
