<?php

namespace App\Http\Controllers\Api\Public;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Api\Admin\LocationController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Public\MapPointResource;
use App\Models\ApplicationCall;
use App\Models\Location;
use App\Models\Program;
use App\Models\Talent;
use Illuminate\Http\Request;

/**
 * Vue consolidée en lecture seule — pas un module de contenu séparé
 * (architecturev1.md §G) : agrège les localisations des entités déjà
 * existantes qui portent un point sur la carte, EN NE RENVOYANT JAMAIS la
 * position d'une entité non publiée (un programme en brouillon ne doit pas
 * apparaître sur la carte publique même s'il a une localisation enregistrée).
 */
#[Group('Cartographie — Public')]
class MapController extends Controller
{
    public function index(Request $request)
    {
        $points = collect();

        foreach (LocationController::LOCATABLE_MAP as $alias => $modelClass) {
            $publishedIds = $this->publishedIdsFor($modelClass);

            $locations = Location::query()
                ->where('locatable_type', (new $modelClass)->getMorphClass())
                ->whereIn('locatable_id', $publishedIds)
                ->with('locatable')
                ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
                ->get();

            foreach ($locations as $location) {
                $points->push(new MapPointResource($location, $alias));
            }
        }

        return MapPointResource::collection($points);
    }

    /**
     * Chaque type géolocalisable a son propre critère de "publié" (scope
     * `published()` pour Program/ApplicationCall, statut `publie` pour
     * Talent) — pas de colonne `statut` universelle à supposer.
     */
    private function publishedIdsFor(string $modelClass): array
    {
        return match ($modelClass) {
            Program::class, ApplicationCall::class => $modelClass::query()->published()->pluck('id')->all(),
            Talent::class => $modelClass::query()->published()->pluck('id')->all(),
            default => [],
        };
    }
}
