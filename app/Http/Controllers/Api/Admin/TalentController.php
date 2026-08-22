<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTalentRequest;
use App\Http\Requests\Admin\UpdateTalentRequest;
use App\Http\Resources\TalentResource;
use App\Models\Talent;
use Illuminate\Http\Request;

#[Group('Talents — Admin')]
class TalentController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Talent::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $talents = Talent::query()
            ->with('domain')
            ->when($request->filled('search'), fn ($q) => $q->where('nom', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->when($request->filled('domain_id'), fn ($q) => $q->where('domain_id', $request->integer('domain_id')))
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return TalentResource::collection($talents);
    }

    public function store(StoreTalentRequest $request)
    {
        $this->authorize('create', Talent::class);

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Talent::generateUniqueSlug($data['nom']);
        $data['statut'] = $data['statut'] ?? 'brouillon';

        $talent = Talent::create($data);

        return (new TalentResource($talent->load('domain')))
            ->additional(['message' => 'Talent créé avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Talent $talent)
    {
        $this->authorize('view', $talent);

        return new TalentResource($talent->load(['domain', 'media', 'location']));
    }

    public function update(UpdateTalentRequest $request, Talent $talent)
    {
        $this->authorize('update', $talent);

        // Même principe que Page/Program/News : un slug déjà publié ne
        // change jamais silencieusement, seul un slug explicitement
        // transmis est pris en compte (pas de régénération automatique sur
        // changement de nom).
        $talent->update($request->validated());

        return (new TalentResource($talent->fresh()->load('domain')))
            ->additional(['message' => 'Talent mis à jour avec succès.']);
    }

    public function destroy(Talent $talent)
    {
        $this->authorize('delete', $talent);

        $talent->delete();

        return response()->json(['message' => 'Talent supprimé avec succès.']);
    }

    /**
     * Export CSV — même filtrage que index().
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Talent::class);

        $talents = Talent::query()
            ->with('domain')
            ->when($request->filled('search'), fn ($q) => $q->where('nom', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->orderByDesc('updated_at')
            ->cursor();

        return $this->streamCsv(
            $talents,
            ['Nom', 'Slug', 'Domaine', 'Région', 'Statut', 'Créé le'],
            fn (Talent $talent) => [
                $talent->nom,
                $talent->slug,
                $talent->domain?->nom,
                $talent->region?->value,
                $talent->statut->value,
                $talent->created_at->toDateString(),
            ],
            'talents'
        );
    }
}
