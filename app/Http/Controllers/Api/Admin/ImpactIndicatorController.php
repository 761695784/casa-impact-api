<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImpactIndicatorResource;
use App\Http\Resources\ImpactValueResource;
use App\Models\ImpactIndicator;
use Illuminate\Http\Request;

/**
 * Gestion admin des indicateurs d'impact et de leurs points de mesure.
 * Protégée uniquement par le middleware du groupe de routes (auth:sanctum) —
 * pas de permission plus fine pour l'instant (comme AdminMessageController),
 * accessible à tous les rôles admin (accord du 2026-09-11).
 *
 * Validation faite en ligne (pas de FormRequest dédiée) : les règles sont
 * volontairement simples (3 champs pour l'indicateur, 3 pour une valeur).
 */
class ImpactIndicatorController extends Controller
{
    public function index(Request $request)
    {
        $indicators = ImpactIndicator::query()
            ->with('values')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('libelle', 'like', '%'.$request->string('search').'%')
                    ->orWhere('description', 'like', '%'.$request->string('search').'%')
            )
            ->orderBy('id')
            ->get();

        return ImpactIndicatorResource::collection($indicators);
    }

    public function show(ImpactIndicator $impactIndicator)
    {
        $impactIndicator->load('values');

        return new ImpactIndicatorResource($impactIndicator);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'min:3', 'max:255'],
            'unite' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $indicator = ImpactIndicator::create($data);

        return (new ImpactIndicatorResource($indicator->load('values')))
            ->additional(['message' => "L'indicateur d'impact a été créé avec succès."])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, ImpactIndicator $impactIndicator)
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'min:3', 'max:255'],
            'unite' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $impactIndicator->update($data);

        return (new ImpactIndicatorResource($impactIndicator->load('values')))
            ->additional(['message' => "L'indicateur d'impact a été mis à jour avec succès."]);
    }

    public function destroy(ImpactIndicator $impactIndicator)
    {
        $impactIndicator->delete();

        return response()->json(['message' => "L'indicateur d'impact a été supprimé avec succès."]);
    }

    /**
     * Ajoute un point de mesure à cet indicateur.
     * Endpoint : POST /api/admin/impact-indicators/{impactIndicator}/values
     */
    public function addValue(Request $request, ImpactIndicator $impactIndicator)
    {
        $data = $request->validate([
            'valeur' => ['required', 'numeric'],
            'periode' => ['nullable', 'string', 'max:50'],
            'region' => ['nullable', 'string', 'in:ziguinchor,sedhiou,kolda'],
        ]);

        $value = $impactIndicator->values()->create($data);

        return (new ImpactValueResource($value))
            ->additional(['message' => 'Le point de mesure a été ajouté avec succès.'])
            ->response()
            ->setStatusCode(201);
    }
}
