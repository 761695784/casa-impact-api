<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\ApplicationCall;
use App\Models\Location;
use App\Models\Program;
use App\Models\Talent;

/**
 * Un seul endpoint upsert (`PUT`) plutôt que store()/update() séparés : côté
 * admin, "définir la position sur la carte" est une action unique et
 * idempotente (au plus une localisation par entité, contrainte unique en
 * DB) — pas besoin de distinguer création/modification pour l'utilisateur.
 */
#[Group('Cartographie — Admin')]
class LocationController extends Controller
{
    public const LOCATABLE_MAP = [
        'program' => Program::class,
        'application-call' => ApplicationCall::class,
        'talent' => Talent::class,
    ];

    public function upsert(StoreLocationRequest $request)
    {
        $this->authorize('locations.manage');

        $data = $request->validated();
        $modelClass = self::LOCATABLE_MAP[$data['locatable_type']];
        $locatable = $modelClass::query()->findOrFail($data['locatable_id']);

        $location = Location::query()->updateOrCreate(
            ['locatable_type' => $locatable->getMorphClass(), 'locatable_id' => $locatable->id],
            [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'libelle' => $data['libelle'] ?? null,
                'region' => $data['region'] ?? null,
            ]
        );

        return (new LocationResource($location))
            ->additional(['message' => 'Localisation enregistrée avec succès.']);
    }

    public function destroy(Location $location)
    {
        $this->authorize('locations.manage');

        $location->delete();

        return response()->json(['message' => 'Localisation supprimée avec succès.']);
    }
}
