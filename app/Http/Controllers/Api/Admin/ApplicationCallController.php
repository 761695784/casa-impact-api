<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreApplicationCallRequest;
use App\Http\Requests\Admin\UpdateApplicationCallRequest;
use App\Http\Resources\ApplicationCallResource;
use App\Models\ApplicationCall;
use Illuminate\Http\Request;

#[Group('Appels à candidatures — Admin')]
class ApplicationCallController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $this->authorize('viewAny', ApplicationCall::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $applicationCalls = ApplicationCall::query()
            ->with('program')
            // Correctif du 2026-09-14 : le compteur "X reçue(s)" affiché
            // côté admin restait toujours à 0, faute de ce withCount — voir
            // ApplicationCallResource::candidatures_count (même principe
            // que ProgramController::index()/appels_count).
            ->withCount('applications')
            ->when($request->filled('search'), fn ($q) => $q->where('titre', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')))
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return ApplicationCallResource::collection($applicationCalls);
    }

    public function store(StoreApplicationCallRequest $request)
    {
        $this->authorize('create', ApplicationCall::class);

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? ApplicationCall::generateUniqueSlug($data['titre']);
        $data['statut'] = $data['statut'] ?? 'brouillon';

        $applicationCall = ApplicationCall::create($data);

        return (new ApplicationCallResource($applicationCall->load('program')))
            ->additional(['message' => "Appel à candidatures créé avec succès."])
            ->response()
            ->setStatusCode(201);
    }

    public function show(ApplicationCall $applicationCall)
    {
        $this->authorize('view', $applicationCall);

        $applicationCall->loadCount('applications');

        return new ApplicationCallResource($applicationCall->load(['program', 'media', 'location']));
    }

    public function update(UpdateApplicationCallRequest $request, ApplicationCall $applicationCall)
    {
        $this->authorize('update', $applicationCall);

        // Même principe que Page/Program : un slug déjà publié ne change
        // jamais silencieusement, seul un slug explicitement transmis est
        // pris en compte.
        $applicationCall->update($request->validated());

        $updated = $applicationCall->fresh()->load('program');
        $updated->loadCount('applications');

        return (new ApplicationCallResource($updated))
            ->additional(['message' => "Appel à candidatures mis à jour avec succès."]);
    }

    public function destroy(ApplicationCall $applicationCall)
    {
        $this->authorize('delete', $applicationCall);

        $applicationCall->delete();

        return response()->json(['message' => "Appel à candidatures supprimé avec succès."]);
    }

    /**
     * Export CSV — ajouté au Module 14 (Dashboard/export).
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', ApplicationCall::class);

        $applicationCalls = ApplicationCall::query()
            ->with('program')
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->orderByDesc('updated_at')
            ->cursor();

        return $this->streamCsv(
            $applicationCalls,
            ['ID', 'Titre', 'Slug', 'Statut', 'Région', 'Programme', 'Places', 'Date limite'],
            fn (ApplicationCall $applicationCall) => [
                $applicationCall->id,
                $applicationCall->titre,
                $applicationCall->slug,
                $applicationCall->statut->value,
                $applicationCall->region?->value,
                $applicationCall->program?->titre,
                $applicationCall->nombre_places,
                $applicationCall->date_limite?->toDateString(),
            ],
            'appels-a-candidatures'
        );
    }
}
