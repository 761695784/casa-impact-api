<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreImpactValueRequest;
use App\Http\Requests\Admin\UpdateImpactValueRequest;
use App\Http\Resources\ImpactValueResource;
use App\Models\ImpactIndicator;
use App\Models\ImpactValue;

/**
 * Nested sous /api/admin/impact-indicators/{impactIndicator}/values — les
 * valeurs n'existent jamais indépendamment de leur indicateur (pas de
 * endpoint "toutes les valeurs" à plat). Les permissions restent celles de
 * ImpactIndicatorPolicy (`impact.*`) : gérer une valeur, c'est gérer
 * l'indicateur.
 */
#[Group('Impact — Admin')]
class ImpactValueController extends Controller
{
    public function store(StoreImpactValueRequest $request, ImpactIndicator $impactIndicator)
    {
        $this->authorize('update', $impactIndicator);

        $value = $impactIndicator->values()->create($request->validated());

        return (new ImpactValueResource($value))
            ->additional(['message' => 'Valeur ajoutée avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateImpactValueRequest $request, ImpactValue $impactValue)
    {
        $this->authorize('update', $impactValue->impactIndicator);

        $impactValue->update($request->validated());

        return (new ImpactValueResource($impactValue->fresh()))
            ->additional(['message' => 'Valeur mise à jour avec succès.']);
    }

    public function destroy(ImpactValue $impactValue)
    {
        $this->authorize('update', $impactValue->impactIndicator);

        $impactValue->delete();

        return response()->json(['message' => 'Valeur supprimée avec succès.']);
    }
}
