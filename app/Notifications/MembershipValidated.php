<?php

namespace App\Notifications;

use App\Models\Membership;
use App\Services\MembershipCardService;
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
 * IMPORTANT (corrigé le 2026-09-10) : la carte PDF est régénérée ICI, dans
 * toMail(), au moment de l'envoi réel — PAS passée en paramètre du
 * constructeur. Une première version stockait le PDF déjà généré (bytes
 * bruts) comme propriété publique du job : comme cette notification est
 * `ShouldQueue`, Laravel sérialise tout son état pour le stocker en file
 * d'attente, et sérialiser une chaîne binaire (PDF) dans l'enveloppe JSON
 * du job échoue ("Unable to JSON encode payload... Malformed UTF-8
 * characters") puisque du binaire brut n'est pas de l'UTF-8 valide.
 * `MembershipCardService` lui-même reste injectable sans problème (aucune
 * propriété binaire, juste un service sans état résolu via le conteneur à
 * l'exécution du job, jamais sérialisé).
 */
class MembershipValidated extends Notification implements ShouldQueue
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
        $cardPdfContent = app(MembershipCardService::class)->generate($this->membership);

        return (new MailMessage)
            ->subject('Bienvenue chez Casa Impact — votre adhésion est validée')
            ->view('emails.memberships.validated', ['membership' => $this->membership])
            ->attachData(
                $cardPdfContent,
                "carte-membre-{$this->membership->numero_membre}.pdf",
                ['mime' => 'application/pdf']
            );
    }
}
