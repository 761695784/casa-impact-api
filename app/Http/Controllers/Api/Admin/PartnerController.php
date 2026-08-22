<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePartnerRequest;
use App\Http\Requests\Admin\UpdatePartnerRequest;
use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use Illuminate\Http\Request;

#[Group('Partenaires — Admin')]
class PartnerController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Partner::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $partners = Partner::query()
            ->with('media')
            ->when($request->filled('search'), fn ($q) => $q->where('nom', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderBy('ordre')
            ->paginate($perPage);

        return PartnerResource::collection($partners);
    }

    public function store(StorePartnerRequest $request)
    {
        $this->authorize('create', Partner::class);

        $data = $request->validated();
        $data['statut'] = $data['statut'] ?? 'actif';
        $data['ordre'] = $data['ordre'] ?? 0;

        $partner = Partner::create($data);

        return (new PartnerResource($partner))
            ->additional(['message' => 'Partenaire créé avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Partner $partner)
    {
        $this->authorize('view', $partner);

        return new PartnerResource($partner->load('media'));
    }

    public function update(UpdatePartnerRequest $request, Partner $partner)
    {
        $this->authorize('update', $partner);

        $partner->update($request->validated());

        return (new PartnerResource($partner->fresh()->load('media')))
            ->additional(['message' => 'Partenaire mis à jour avec succès.']);
    }

    public function destroy(Partner $partner)
    {
        $this->authorize('delete', $partner);

        $partner->delete();

        return response()->json(['message' => 'Partenaire supprimé avec succès.']);
    }

    /**
     * Export CSV — même filtrage que index().
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Partner::class);

        $partners = Partner::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nom', 'like', "%{$request->string('search')}%"))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderBy('ordre')
            ->cursor();

        return $this->streamCsv(
            $partners,
            ['Nom', 'Type', 'Statut', 'Ordre', 'Lien'],
            fn (Partner $partner) => [
                $partner->nom,
                $partner->type?->value,
                $partner->statut->value,
                $partner->ordre,
                $partner->lien,
            ],
            'partenaires'
        );
    }
}
