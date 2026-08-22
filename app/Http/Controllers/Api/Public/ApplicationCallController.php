<?php

namespace App\Http\Controllers\Api\Public;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationCallResource;
use App\Models\ApplicationCall;
use Illuminate\Http\Request;

#[Group('Appels à candidatures — Public')]
class ApplicationCallController extends Controller
{
    /**
     * Aucune authentification requise. Ne renvoie jamais un appel en
     * brouillon ou fermé. Filtrage optionnel par région et par slug de
     * programme (paramètres explicites, jamais de requête libre — voir
     * architecturev1.md §F).
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $applicationCalls = ApplicationCall::query()
            ->published()
            ->with('program')
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->when(
                $request->filled('program'),
                fn ($q) => $q->whereHas('program', fn ($p) => $p->where('slug', $request->string('program')))
            )
            ->orderByDesc('date_limite')
            ->paginate($perPage);

        return ApplicationCallResource::collection($applicationCalls);
    }

    public function show(string $slug)
    {
        $applicationCall = ApplicationCall::query()
            ->published()
            ->with(['program', 'media', 'location'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ApplicationCallResource($applicationCall);
    }
}
