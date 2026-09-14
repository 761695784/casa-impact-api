<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\Cartography\CartographyAggregator;
use Illuminate\Http\JsonResponse;

/**
 * Vue publique de la Cartographie Territoriale (page /carte du site public) —
 * uniquement les éléments publiés (voir CartographyAggregator::collect()),
 * aucune authentification requise.
 */
class CartographyController extends Controller
{
    public function __construct(private CartographyAggregator $aggregator)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->aggregator->collect(publicOnly: true)]);
    }
}
