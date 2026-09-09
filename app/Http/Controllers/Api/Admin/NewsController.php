<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNewsRequest;
use App\Http\Requests\Admin\UpdateNewsRequest;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $this->authorize('viewAny', News::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $news = News::query()
            // Nécessaire pour que la couverture et l'album (collections
            // "cover"/"gallery" de la médiathèque) s'affichent déjà dans la
            // liste admin, pas seulement sur la fiche détail — sinon
            // rouvrir le formulaire d'édition depuis la liste réinitialise
            // à tort la sélection de photos à vide côté frontend.
            ->with('media')
            ->when($request->filled('search'), fn ($q) => $q->where('titre', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return NewsResource::collection($news);
    }

    public function store(StoreNewsRequest $request)
    {
        $this->authorize('create', News::class);

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? News::generateUniqueSlug($data['titre']);
        $data['statut'] = $data['statut'] ?? 'brouillon';

        $news = News::create($data);

        return (new NewsResource($news))
            ->additional(['message' => 'Actualité créée avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(News $news)
    {
        $this->authorize('view', $news);

        return new NewsResource($news->load('media'));
    }

    public function update(UpdateNewsRequest $request, News $news)
    {
        $this->authorize('update', $news);

        // Même principe que Page/Program : un slug déjà publié ne change
        // jamais silencieusement, seul un slug explicitement transmis est
        // pris en compte.
        $news->update($request->validated());

        // fresh('media') : sans quoi whenLoaded('media') dans NewsResource
        // resterait "non chargé" après un update et l'API omettrait la clé
        // `media` de la réponse, y compris juste après avoir rattaché une
        // couverture/galerie côté frontend (ActualiteFormDialog::syncMedia).
        return (new NewsResource($news->fresh('media')))
            ->additional(['message' => 'Actualité mise à jour avec succès.']);
    }

    public function destroy(News $news)
    {
        $this->authorize('delete', $news);

        $news->delete();

        return response()->json(['message' => 'Actualité supprimée avec succès.']);
    }

    /**
     * Export CSV — ajouté au Module 14 (Dashboard/export).
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', News::class);

        $news = News::query()
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('updated_at')
            ->cursor();

        return $this->streamCsv(
            $news,
            ['ID', 'Titre', 'Slug', 'Type', 'Statut', 'Créée le'],
            fn (News $item) => [
                $item->id,
                $item->titre,
                $item->slug,
                $item->type->value,
                $item->statut->value,
                $item->created_at->toDateString(),
            ],
            'actualites'
        );
    }
}
