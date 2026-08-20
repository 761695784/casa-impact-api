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
            ->where('slug', $slug)
            ->firstOrFail();

        return new NewsResource($news);
    }
}
