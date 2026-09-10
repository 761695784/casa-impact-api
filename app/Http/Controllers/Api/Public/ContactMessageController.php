<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use App\Notifications\NewContactMessageNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Aucune authentification requise. Volontairement ne renvoie JAMAIS de
 * Resource (aucune Resource publique n'existe pour ContactMessage — données
 * personnelles, voir Admin\ContactMessageResource) : seulement un message de
 * confirmation. `statut` est toujours forcé à `nouveau`, quelle que soit la
 * donnée envoyée par le client.
 *
 * MIS À JOUR le 2026-08-24 : envoie ContactMessageReceived (réponse
 * automatique brandée) au visiteur après la création.
 *
 * MIS À JOUR le 2026-09-10 : envoie EN PLUS NewContactMessageNotification
 * à l'adresse officielle Casa Impact (config('casaimpact.signature_email'))
 * — demande explicite de l'utilisateur, pour ne plus dépendre uniquement
 * de la consultation du panneau admin.
 */
class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request)
    {
        $data = $request->validated();
        $data['statut'] = 'nouveau';

        $contactMessage = ContactMessage::create($data);

        Notification::route('mail', $contactMessage->email)
            ->notify(new ContactMessageReceived($contactMessage));

        Notification::route('mail', config('casaimpact.signature_email'))
            ->notify(new NewContactMessageNotification($contactMessage));

        return response()->json([
            'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.',
        ], 201);
    }
}
