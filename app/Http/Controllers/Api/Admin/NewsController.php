<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNewsRequest;
use App\Http\Requests\Admin\UpdateNewsRequest;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', News::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $news = News::query()
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

        return new NewsResource($news);
    }

    public function update(UpdateNewsRequest $request, News $news)
    {
        $this->authorize('update', $news);

        // Même principe que Page/Program : un slug déjà publié ne change
        // jamais silencieusement, seul un slug explicitement transmis est
        // pris en compte.
        $news->update($request->validated());

        return (new NewsResource($news->fresh()))
            ->additional(['message' => 'Actualité mise à jour avec succès.']);
    }

    public function destroy(News $news)
    {
        $this->authorize('delete', $news);

        $news->delete();

        return response()->json(['message' => 'Actualité supprimée avec succès.']);
    }
}
