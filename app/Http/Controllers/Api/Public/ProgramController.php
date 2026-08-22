<?php

namespace App\Http\Controllers\Api\Public;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Illuminate\Http\Request;

#[Group('Programmes — Public')]
class ProgramController extends Controller
{
    /**
     * Aucune authentification requise. Ne renvoie jamais un programme en
     * brouillon ou archivé. Filtrage optionnel par slug de domaine ou de
     * type (paramètres explicites, jamais de requête libre — voir
     * architecturev1.md §F).
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $programs = Program::query()
            ->published()
            ->with(['domain', 'programType'])
            ->when(
                $request->filled('domain'),
                fn ($q) => $q->whereHas('domain', fn ($d) => $d->where('slug', $request->string('domain')))
            )
            ->when(
                $request->filled('type'),
                fn ($q) => $q->whereHas('programType', fn ($t) => $t->where('slug', $request->string('type')))
            )
            ->orderByDesc('date_debut')
            ->paginate($perPage);

        return ProgramResource::collection($programs);
    }

    public function show(string $slug)
    {
        $program = Program::query()
            ->published()
            ->with(['domain', 'programType', 'media', 'location'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProgramResource($program);
    }
}
