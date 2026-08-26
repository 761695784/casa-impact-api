<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoi de confirmation de candidature — TOUJOURS en file (ShouldQueue),
 * jamais d'envoi inline dans le contrôleur (architecturev1.md §I).
 *
 * MIS À JOUR le 2026-08-24 : le contenu passe désormais par une vue Blade
 * "maison" (emails.applications.received, via emails.layout — logo,
 * filigrane, signature Casa Impact) au lieu du builder par défaut de
 * MailMessage — demande explicite de l'utilisateur ("les mails doivent y
 * avoir le logo de casa impact et des filigranes... et aussi une ou des
 * signatures"). La logique métier (branche liste d'attente) est
 * maintenant dans la vue elle-même plutôt que construite ici avec
 * ->line(), voir le fichier Blade pour le détail.
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
        return (new MailMessage)
            ->subject('Confirmation de votre candidature — Casa Impact')
            ->view('emails.applications.received', ['application' => $this->application->loadMissing('applicationCall')]);
    }
}
