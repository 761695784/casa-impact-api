<?php

namespace App\Notifications;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoyée quand une adhésion passe (ou est créée) au statut `validee` —
 * voir Admin\MembershipController::update()/store(). Contient la carte de
 * membre PDF en pièce jointe (générée à la volée via attachData(), pas
 * stockée sur disque : la carte peut toujours être régénérée à
 * l'identique depuis les données de l'adhésion, inutile de la persister),
 * le lien du groupe WhatsApp et le lien des réseaux sociaux — contenu
 * calé sur l'exemple réel fourni par l'utilisateur le 2026-08-24.
 *
 * IMPORTANT : le constructeur reçoit le PDF déjà généré (bytes), pas le
 * service lui-même — MembershipCardService dépend de la façade DomPDF
 * (`Barryvdh\DomPDF\Facade\Pdf`), qui n'est PAS sérialisable proprement
 * pour la file d'attente. Générer la carte avant de dispatcher la
 * notification (voir contrôleur) évite tout problème de sérialisation
 * d'un job ShouldQueue.
 */
class MembershipValidated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Membership $membership, public string $cardPdfContent)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue chez Casa Impact — votre adhésion est validée')
            ->view('emails.memberships.validated', ['membership' => $this->membership])
            ->attachData(
                $this->cardPdfContent,
                "carte-membre-{$this->membership->numero_membre}.pdf",
                ['mime' => 'application/pdf']
            );
    }
}
