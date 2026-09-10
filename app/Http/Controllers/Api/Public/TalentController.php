<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\TalentResource;
use App\Models\Talent;
use Illuminate\Http\Request;

class TalentController extends Controller
{
    /**
     * Aucune authentification requise. Ne renvoie jamais un talent en
     * brouillon ou archivé.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $talents = Talent::query()
            ->published()
            ->with(['domain', 'media'])
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->when($request->filled('domain_id'), fn ($q) => $q->where('domain_id', $request->integer('domain_id')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return TalentResource::collection($talents);
    }

    /**
     * Les talents ont une page de détail publique (portrait/success story)
     * — media/location sont donc eager-loadés ici, contrairement à
     * Testimonial qui n'a pas d'équivalent show().
     */
    public function show(string $slug)
    {
        $talent = Talent::query()
            ->published()
            ->with(['domain', 'media', 'location'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new TalentResource($talent);
    }
}
