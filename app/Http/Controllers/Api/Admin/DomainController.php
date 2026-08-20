<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDomainRequest;
use App\Http\Resources\DomainResource;
use App\Models\Domain;

/**
 * Volontairement pas de store()/destroy() : les 6 domaines sont un
 * référentiel fixe seedé par DomainsSeeder (voir DomainPolicy).
 */
class DomainController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Domain::class);

        return DomainResource::collection(
            Domain::query()->orderBy('ordre')->get()
        );
    }

    public function show(Domain $domain)
    {
        $this->authorize('view', $domain);

        return new DomainResource($domain);
    }

    public function update(UpdateDomainRequest $request, Domain $domain)
    {
        $this->authorize('update', $domain);

        $domain->update($request->validated());

        return (new DomainResource($domain->fresh()))
            ->additional(['message' => 'Domaine mis à jour avec succès.']);
    }
}
