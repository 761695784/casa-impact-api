<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use Illuminate\Http\Request;

/**
 * Index uniquement — volontairement PAS de show(). Un témoignage est une
 * courte citation sans slug ni page de détail dédiée (voir brief Module 10
 * et Testimonial, qui n'expose pas generateUniqueSlug()) : il n'existe donc
 * aucune route publique adressant un témoignage individuellement.
 */
class TestimonialController extends Controller
{
    /**
     * Aucune authentification requise. Ne renvoie jamais un témoignage en
     * brouillon ou archivé. `program`/`applicationCall` ne sont volontairement
     * PAS eager-loadés ici : la liste publique des témoignages est affichée
     * comme un simple carrousel de citations (auteur + citation), le
     * programme/appel d'origine n'apporte pas de valeur visible à cet
     * endroit et éviterait juste une requête inutile côté public à fort
     * trafic. Le contrôleur admin, lui, les charge (utile en back-office).
     * `media` (photo du témoin), en revanche, EST affiché sur cette liste
     * publique — doit donc être eager-chargé ici.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $testimonials = Testimonial::query()
            ->with('media')
            ->published()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return TestimonialResource::collection($testimonials);
    }
}
