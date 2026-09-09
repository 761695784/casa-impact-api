<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Aucune authentification requise. Ne renvoie jamais une actualité en
     * brouillon, en prévisualisation, ou archivée.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $news = News::query()
            // Sans ce with('media'), whenLoaded('media') dans NewsResource
            // ne renvoie jamais la couverture sur la liste publique (seule
            // la fiche détail, show(), la chargeait) — la vitrine retombait
            // alors sur une image de repli identique pour chaque article.
            ->with('media')
            ->published()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return NewsResource::collection($news);
    }

    public function show(string $slug)
    {
        $news = News::query()
            ->published()
            ->with('media')
            ->where('slug', $slug)
            ->firstOrFail();

        return new NewsResource($news);
    }
}
