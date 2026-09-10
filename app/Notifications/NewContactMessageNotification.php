<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification interne envoyée à l'adresse officielle Casa Impact
 * (config('casaimpact.signature_email')) à chaque nouveau message reçu
 * via le formulaire de contact du site — demande explicite du 2026-09-10,
 * en complément de ContactMessageReceived (réponse automatique au
 * visiteur, livraison du 2026-08-24) et de l'apparition du message dans
 * /admin/messages. Même layout brandé que le reste des emails Casa
 * Impact.
 */
class NewContactMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau message reçu via Formulaire de contact du site de Casa Impact')
            ->view('emails.contact.new-message-internal', [
                'contactMessage' => $this->contactMessage,
                'categorieLabel' => $this->categorieLabel(),
            ]);
    }

    /**
     * Même correspondance que CONTACT_CATEGORY_LABELS côté frontend
     * (types/enums.ts) — pas de label() sur l'enum ContactCategory côté
     * backend, donc reproduit ici plutôt que d'ajouter une dépendance
     * croisée entre la Notification et l'enum pour ce seul usage.
     */
    private function categorieLabel(): string
    {
        return match ($this->contactMessage->categorie->value) {
            'information_generale' => 'Information générale',
            'partenariat' => 'Partenariat',
            'investissement' => 'Investissement',
            'diaspora' => 'Diaspora',
            'projet' => 'Projet',
            'autre' => 'Autre',
            default => ucfirst($this->contactMessage->categorie->value),
        };
    }
}
