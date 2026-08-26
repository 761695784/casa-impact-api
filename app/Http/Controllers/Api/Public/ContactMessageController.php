<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use Illuminate\Support\Facades\Notification;

/**
 * Aucune authentification requise. Volontairement ne renvoie JAMAIS de
 * Resource (aucune Resource publique n'existe pour ContactMessage — données
 * personnelles, voir Admin\ContactMessageResource) : seulement un message de
 * confirmation. `statut` est toujours forcé à `nouveau`, quelle que soit la
 * donnée envoyée par le client.
 *
 * MIS À JOUR le 2026-08-24 : envoie désormais ContactMessageReceived
 * (réponse automatique brandée) après la création — demande explicite de
 * l'utilisateur. Pour les emails envoyés DIRECTEMENT à l'adresse Casa
 * Impact (hors formulaire du site), voir le README : la réponse
 * automatique passe par le répondeur natif de Gmail, pas par ce code.
 */
class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request)
    {
        $data = $request->validated();
        $data['statut'] = 'nouveau';

        $contactMessage = ContactMessage::create($data);

        Notification::route('mail', $contactMessage->email)->notify(new ContactMessageReceived($contactMessage));

        return response()->json([
            'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.',
        ], 201);
    }
}
