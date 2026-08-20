<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Program::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $programs = Program::query()
            ->with(['domain', 'programType'])
            ->when($request->filled('search'), fn ($q) => $q->where('titre', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('domain_id'), fn ($q) => $q->where('domain_id', $request->integer('domain_id')))
            ->when($request->filled('program_type_id'), fn ($q) => $q->where('program_type_id', $request->integer('program_type_id')))
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return ProgramResource::collection($programs);
    }

    public function store(StoreProgramRequest $request)
    {
        $this->authorize('create', Program::class);

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Program::generateUniqueSlug($data['titre']);
        $data['statut'] = $data['statut'] ?? 'brouillon';

        $program = Program::create($data);

        return (new ProgramResource($program->load(['domain', 'programType'])))
            ->additional(['message' => 'Programme créé avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Program $program)
    {
        $this->authorize('view', $program);

        return new ProgramResource($program->load(['domain', 'programType']));
    }

    public function update(UpdateProgramRequest $request, Program $program)
    {
        $this->authorize('update', $program);

        // Même principe que Page : un slug déjà publié ne change jamais
        // silencieusement, seul un slug explicitement transmis est pris en
        // compte (pas de régénération automatique sur changement de titre).
        $program->update($request->validated());

        return (new ProgramResource($program->fresh()->load(['domain', 'programType'])))
            ->additional(['message' => 'Programme mis à jour avec succès.']);
    }

    public function destroy(Program $program)
    {
        $this->authorize('delete', $program);

        $program->delete();

        return response()->json(['message' => 'Programme supprimé avec succès.']);
    }
}
