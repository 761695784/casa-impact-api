<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Réponse automatique à toute soumission du formulaire de contact du site
 * — demande explicite du 2026-08-24. Pour les emails envoyés DIRECTEMENT
 * à l'adresse officielle Casa Impact (hors formulaire du site), voir le
 * README : la réponse automatique passe par le répondeur natif de Gmail
 * (choix explicite de l'utilisateur), PAS par ce code Laravel.
 */
class ContactMessageReceived extends Notification implements ShouldQueue
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
            ->subject('Nous avons bien reçu votre message — Casa Impact')
            ->view('emails.contact.received', ['contactMessage' => $this->contactMessage]);
    }
}
