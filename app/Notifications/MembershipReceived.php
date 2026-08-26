<?php

namespace App\Notifications;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoyée immédiatement après la soumission publique du formulaire
 * d'adhésion (Public\MembershipController::store()) — contient le numéro
 * de dossier généré et les instructions de paiement Wave, voir
 * emails.memberships.received (contenu calé sur l'exemple réel fourni par
 * l'utilisateur le 2026-08-24).
 *
 * N'est PAS envoyée pour une adhésion saisie manuellement par l'admin
 * (source = manuel) : voir Admin\MembershipController::store(), qui crée
 * directement au statut `validee` et envoie MembershipValidated à la
 * place — sauf si `send_welcome_email` est explicitement à false (adhérent
 * historique, aucun email de bienvenue "aujourd'hui" dans ce cas).
 */
class MembershipReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Membership $membership)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirmation de votre demande d\'adhésion — Casa Impact')
            ->view('emails.memberships.received', ['membership' => $this->membership]);
    }
}
