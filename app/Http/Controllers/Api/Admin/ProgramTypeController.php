<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramTypeRequest;
use App\Http\Requests\Admin\UpdateProgramTypeRequest;
use App\Http\Resources\ProgramTypeResource;
use App\Models\ProgramType;
use Illuminate\Http\Request;

class ProgramTypeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ProgramType::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $programTypes = ProgramType::query()
            ->withCount('programs')
            ->when($request->filled('search'), fn ($q) => $q->where('nom', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->orderBy('ordre')
            ->orderBy('nom')
            ->paginate($perPage);

        return ProgramTypeResource::collection($programTypes);
    }

    public function store(StoreProgramTypeRequest $request)
    {
        $this->authorize('create', ProgramType::class);

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? ProgramType::generateUniqueSlug($data['nom']);
        $data['statut'] = $data['statut'] ?? 'actif';

        $programType = ProgramType::create($data);

        return (new ProgramTypeResource($programType))
            ->additional(['message' => "Type de programme créé avec succès."])
            ->response()
            ->setStatusCode(201);
    }

    public function show(ProgramType $programType)
    {
        $this->authorize('view', $programType);

        return new ProgramTypeResource($programType->loadCount('programs'));
    }

    public function update(UpdateProgramTypeRequest $request, ProgramType $programType)
    {
        $this->authorize('update', $programType);

        $programType->update($request->validated());

        return (new ProgramTypeResource($programType->fresh()->loadCount('programs')))
            ->additional(['message' => "Type de programme mis à jour avec succès."]);
    }

    public function destroy(ProgramType $programType)
    {
        $this->authorize('delete', $programType);

        // 409 explicite plutôt que de laisser remonter l'exception SQL du
        // restrictOnDelete défini en migration.
        if ($programType->programs()->exists()) {
            abort(409, "Ce type de programme est utilisé par au moins un programme et ne peut pas être supprimé.");
        }

        $programType->delete();

        return response()->json(['message' => "Type de programme supprimé avec succès."]);
    }
}
