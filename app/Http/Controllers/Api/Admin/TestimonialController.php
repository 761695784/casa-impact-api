<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTestimonialRequest;
use App\Http\Requests\Admin\UpdateTestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Testimonial::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $testimonials = Testimonial::query()
            // Sans ce with('media'), la photo du témoin n'apparaissait ni
            // dans la grille admin ni, une fois rouvert, dans le formulaire
            // d'édition (seul show() la chargeait) — même défaut que
            // NewsController::index() corrigé le 2026-09-09.
            ->with(['program', 'applicationCall', 'media'])
            ->when($request->filled('search'), fn ($q) => $q->where('auteur', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')))
            ->when($request->filled('application_call_id'), fn ($q) => $q->where('application_call_id', $request->integer('application_call_id')))
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return TestimonialResource::collection($testimonials);
    }

    public function store(StoreTestimonialRequest $request)
    {
        $this->authorize('create', Testimonial::class);

        $data = $request->validated();
        $data['statut'] = $data['statut'] ?? 'brouillon';

        $testimonial = Testimonial::create($data);

        return (new TestimonialResource($testimonial))
            ->additional(['message' => 'Témoignage créé avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Testimonial $testimonial)
    {
        $this->authorize('view', $testimonial);

        return new TestimonialResource($testimonial->load(['program', 'applicationCall', 'media']));
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial)
    {
        $this->authorize('update', $testimonial);

        $testimonial->update($request->validated());

        // fresh(['program', 'applicationCall', 'media']) : un simple
        // fresh() perd les relations chargées, ce qui aurait fait
        // disparaître la photo rattachée juste avant dans la même requête
        // de sauvegarde côté frontend (TemoignageFormDialog::syncPhoto).
        return (new TestimonialResource($testimonial->fresh(['program', 'applicationCall', 'media'])))
            ->additional(['message' => 'Témoignage mis à jour avec succès.']);
    }

    public function destroy(Testimonial $testimonial)
    {
        $this->authorize('delete', $testimonial);

        $testimonial->delete();

        return response()->json(['message' => 'Témoignage supprimé avec succès.']);
    }

    /**
     * Export CSV — même filtrage que index().
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Testimonial::class);

        $testimonials = Testimonial::query()
            ->with(['program', 'applicationCall'])
            ->when($request->filled('search'), fn ($q) => $q->where('auteur', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->orderByDesc('updated_at')
            ->cursor();

        return $this->streamCsv(
            $testimonials,
            ['Auteur', 'Rôle / organisation', 'Citation', 'Statut', 'Programme', 'Appel à candidatures', 'Créé le'],
            fn (Testimonial $testimonial) => [
                $testimonial->auteur,
                $testimonial->role_organisation,
                $testimonial->citation,
                $testimonial->statut->value,
                $testimonial->program?->titre,
                $testimonial->applicationCall?->titre,
                $testimonial->created_at->toDateString(),
            ],
            'temoignages'
        );
    }
}
