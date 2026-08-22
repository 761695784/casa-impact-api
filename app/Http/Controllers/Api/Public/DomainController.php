<?php

namespace App\Http\Controllers\Api\Public;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Resources\DomainResource;
use App\Models\Domain;

#[Group('Domaines — Public')]
class DomainController extends Controller
{
    public function index()
    {
        return DomainResource::collection(
            Domain::query()->active()->orderBy('ordre')->get()
        );
    }

    public function show(string $slug)
    {
        $domain = Domain::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        return new DomainResource($domain);
    }
}
