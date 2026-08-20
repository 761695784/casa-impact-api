<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Page::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $pages = Page::query()
            ->when($request->filled('search'), fn ($q) => $q->where('titre', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return PageResource::collection($pages);
    }

    public function store(StorePageRequest $request)
    {
        $this->authorize('create', Page::class);

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Page::generateUniqueSlug($data['titre']);
        $data['statut'] = $data['statut'] ?? 'brouillon';

        $page = Page::create($data);

        return (new PageResource($page))
            ->additional(['message' => 'Page créée avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Page $page)
    {
        $this->authorize('view', $page);

        return new PageResource($page);
    }

    public function update(UpdatePageRequest $request, Page $page)
    {
        $this->authorize('update', $page);

        $data = $request->validated();

        // Si un nouveau titre est fourni sans slug explicite, on ne
        // régénère PAS le slug automatiquement : un slug déjà publié ne
        // doit jamais changer silencieusement (casserait les liens
        // partagés/référencés). Seul un slug explicitement transmis est pris
        // en compte.
        $page->update($data);

        return (new PageResource($page->fresh()))
            ->additional(['message' => 'Page mise à jour avec succès.']);
    }

    public function destroy(Page $page)
    {
        $this->authorize('delete', $page);

        $page->delete();

        return response()->json(['message' => 'Page supprimée avec succès.']);
    }
}
