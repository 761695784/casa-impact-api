<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreImpactIndicatorRequest;
use App\Http\Requests\Admin\UpdateImpactIndicatorRequest;
use App\Http\Resources\ImpactIndicatorResource;
use App\Models\ImpactIndicator;
use Illuminate\Http\Request;

#[Group('Impact — Admin')]
class ImpactIndicatorController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $this->authorize('viewAny', ImpactIndicator::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $indicators = ImpactIndicator::query()
            ->when($request->filled('search'), fn ($q) => $q->where('libelle', 'like', "%{$request->string('search')}%"))
            ->orderBy('libelle')
            ->paginate($perPage);

        return ImpactIndicatorResource::collection($indicators);
    }

    public function store(StoreImpactIndicatorRequest $request)
    {
        $this->authorize('create', ImpactIndicator::class);

        $indicator = ImpactIndicator::create($request->validated());

        return (new ImpactIndicatorResource($indicator))
            ->additional(['message' => 'Indicateur créé avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(ImpactIndicator $impactIndicator)
    {
        $this->authorize('view', $impactIndicator);

        return new ImpactIndicatorResource($impactIndicator->load('values'));
    }

    public function update(UpdateImpactIndicatorRequest $request, ImpactIndicator $impactIndicator)
    {
        $this->authorize('update', $impactIndicator);

        $impactIndicator->update($request->validated());

        return (new ImpactIndicatorResource($impactIndicator->fresh()))
            ->additional(['message' => 'Indicateur mis à jour avec succès.']);
    }

    public function destroy(ImpactIndicator $impactIndicator)
    {
        $this->authorize('delete', $impactIndicator);

        // Suppression en cascade des valeurs (contrainte FK cascadeOnDelete
        // sur impact_values.impact_indicator_id) — pas de nettoyage manuel
        // nécessaire ici.
        $impactIndicator->delete();

        return response()->json(['message' => 'Indicateur supprimé avec succès.']);
    }

    /**
     * Export CSV — une ligne par valeur, avec le libellé de l'indicateur
     * dénormalisé pour rester lisible dans un tableur (Module 14).
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', ImpactIndicator::class);

        $indicators = ImpactIndicator::query()->with('values')->orderBy('libelle')->get();

        $rows = $indicators->flatMap(
            fn (ImpactIndicator $indicator) => $indicator->values->map(fn ($value) => [$indicator, $value])
        );

        return $this->streamCsv(
            $rows,
            ['Indicateur', 'Unité', 'Valeur', 'Période', 'Région'],
            fn (array $pair) => [
                $pair[0]->libelle,
                $pair[0]->unite,
                (float) $pair[1]->valeur,
                $pair[1]->periode,
                $pair[1]->region?->value,
            ],
            'indicateurs-impact'
        );
    }
}
