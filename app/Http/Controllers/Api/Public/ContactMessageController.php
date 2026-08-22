<?php

namespace App\Http\Controllers\Api\Public;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreContactMessageRequest;
use App\Models\ContactMessage;

/**
 * Aucune authentification requise. Volontairement ne renvoie JAMAIS de
 * Resource (aucune Resource publique n'existe pour ContactMessage — données
 * personnelles, voir Admin\ContactMessageResource) : seulement un message de
 * confirmation. `statut` est toujours forcé à `nouveau`, quelle que soit la
 * donnée envoyée par le client.
 */
#[Group('Contact — Public')]
class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request)
    {
        $data = $request->validated();
        $data['statut'] = 'nouveau';

        ContactMessage::create($data);

        return response()->json([
            'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.',
        ], 201);
    }
}
