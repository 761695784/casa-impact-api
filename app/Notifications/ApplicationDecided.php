<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoyée quand l'admin fait passer une candidature à un statut de
 * DÉCISION (Retenue / NonRetenue / EnListeAttente) via
 * Admin\ApplicationController::update() — demande explicite du
 * 2026-08-24 : "il doit exister une feature qui quand on clique sur
 * valider ou refuser ou en liste d'attente... les mails c'est par
 * formation ou appel a candidature", d'où la référence à
 * $application->applicationCall->titre dans chacune des 3 vues.
 *
 * Ne gère PAS Nouvelle/EnCoursEtude (pas des décisions) — voir
 * ApplicationController::update() pour la logique qui décide quand
 * déclencher cette notification plutôt que ApplicationSubmitted.
 */
class ApplicationDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Application $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $application = $this->application->loadMissing('applicationCall');

        [$subject, $view] = match ($application->statut) {
            ApplicationStatus::Retenue => ['Votre candidature a été retenue — Casa Impact', 'emails.applications.decided-retenue'],
            ApplicationStatus::NonRetenue => ['Suite à votre candidature — Casa Impact', 'emails.applications.decided-non-retenue'],
            ApplicationStatus::EnListeAttente => ["Votre candidature est en liste d'attente — Casa Impact", 'emails.applications.decided-en-liste-attente'],
            default => ['Mise à jour de votre candidature — Casa Impact', 'emails.applications.decided-non-retenue'],
        };

        return (new MailMessage)
            ->subject($subject)
            ->view($view, ['application' => $application]);
    }
}
