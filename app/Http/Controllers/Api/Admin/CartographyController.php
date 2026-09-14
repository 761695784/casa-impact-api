<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Cartography\CartographyAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vue admin de la Cartographie Territoriale : tous les points (y compris
 * brouillons), pour que l'équipe puisse vérifier ce qui sera visible avant
 * publication. Protégée uniquement par le middleware du groupe de routes
 * (auth:sanctum) — pas de permission plus fine pour l'instant, comme
 * `AdminMessageController::send()` (accord du 2026-09-11 : la Cartographie
 * reste visible par tous les rôles, aucune action n'y est possible).
 *
 * `search`/`type`/`region` (query params) filtrent côté serveur pour rester
 * cohérent avec cartography.service.ts, mais un filtrage simple côté
 * collection suffit ici (pas de pagination : quelques dizaines de points au
 * total, pas des milliers).
 */
class CartographyController extends Controller
{
    public function __construct(private CartographyAggregator $aggregator)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $points = collect($this->aggregator->collect(publicOnly: false));

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $points = $points->filter(function (array $p) use ($needle) {
                return str_contains(mb_strtolower((string) $p['titre']), $needle)
                    || str_contains(mb_strtolower((string) $p['commune']), $needle)
                    || str_contains(mb_strtolower((string) $p['departement']), $needle)
                    || str_contains(mb_strtolower((string) $p['description']), $needle)
                    || str_contains(mb_strtolower((string) $p['domaine_nom']), $needle);
            });
        }

        $type = $request->query('type');
        if ($type && $type !== 'all') {
            $points = $points->where('type', $type);
        }

        $region = $request->query('region');
        if ($region && $region !== 'all') {
            $points = $points->where('region', $region);
        }

        return response()->json(['data' => $points->values()->all()]);
    }
}
