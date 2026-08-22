<?php

namespace App\Http\Controllers\Api\Public;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use Illuminate\Http\Request;

/**
 * Volontairement index() SEULEMENT — pas de show() : aucune page de détail
 * individuelle pour un partenaire dans cette version (pas de slug non plus
 * sur le modèle). Ne renvoie jamais un partenaire inactif.
 */
#[Group('Partenaires — Public')]
class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->integer('per_page', 50), 100);

        $partners = Partner::query()
            ->active()
            ->with('media')
            ->orderBy('ordre')
            ->paginate($perPage);

        return PartnerResource::collection($partners);
    }
}
