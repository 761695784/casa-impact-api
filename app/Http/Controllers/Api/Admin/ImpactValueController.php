<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImpactValueResource;
use App\Models\ImpactValue;
use Illuminate\Http\Request;

/**
 * Un point de mesure n'existe jamais seul côté frontend (toujours créé via
 * ImpactIndicatorController::addValue()), mais se modifie/supprime par son
 * propre id une fois créé — d'où ce contrôleur séparé à la racine
 * `/api/admin/impact-values/{impactValue}` (pas imbriqué sous l'indicateur).
 */
class ImpactValueController extends Controller
{
    public function update(Request $request, ImpactValue $impactValue)
    {
        $data = $request->validate([
            'valeur' => ['required', 'numeric'],
            'periode' => ['nullable', 'string', 'max:50'],
            'region' => ['nullable', 'string', 'in:ziguinchor,sedhiou,kolda'],
        ]);

        $impactValue->update($data);

        return (new ImpactValueResource($impactValue))
            ->additional(['message' => 'Le point de mesure a été mis à jour avec succès.']);
    }

    public function destroy(ImpactValue $impactValue)
    {
        $impactValue->delete();

        return response()->json(['message' => 'Le point de mesure a été supprimé avec succès.']);
    }
}
