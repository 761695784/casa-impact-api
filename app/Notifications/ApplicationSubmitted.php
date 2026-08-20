<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoi de confirmation de candidature — TOUJOURS en file (ShouldQueue),
 * jamais d'envoi inline dans le contrôleur (architecturev1.md §I). Fonctionne
 * dès aujourd'hui avec MAIL_MAILER=log (email loggué plutôt qu'envoyé) — un
 * vrai SMTP sera nécessaire avant la mise en production (point déjà connu,
 * non bloquant pour ce module).
 */
class ApplicationSubmitted extends Notification implements ShouldQueue
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
        $message = (new MailMessage)
            ->subject('Confirmation de votre candidature — Casa Impact')
            ->greeting("Bonjour {$this->application->prenom},")
            ->line("Nous avons bien reçu votre candidature. Votre référence est : {$this->application->reference}.");

        if ($this->application->statut === ApplicationStatus::EnListeAttente) {
            $message->line("Le nombre de places pour cet appel est atteint : votre candidature a été placée en liste d'attente. Nous vous recontacterons si une place se libère.");
        } else {
            $message->line("Votre candidature est en cours d'examen. Nous reviendrons vers vous dès que possible.");
        }

        return $message->line('Merci de votre intérêt pour Casa Impact.');
    }
}
