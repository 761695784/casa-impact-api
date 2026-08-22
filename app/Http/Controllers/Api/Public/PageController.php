<?php

namespace App\Http\Controllers\Api\Public;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;

#[Group('Pages — Public')]
class PageController extends Controller
{
    /**
     * Aucune authentification requise (route /api/public/*). Ne renvoie
     * jamais un brouillon ou un contenu archivé.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $pages = Page::query()
            ->published()
            ->orderBy('titre')
            ->paginate($perPage);

        return PageResource::collection($pages);
    }

    public function show(string $slug)
    {
        $page = Page::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return new PageResource($page);
    }
}
