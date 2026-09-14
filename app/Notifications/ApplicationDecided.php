<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email contextuel pour le statut d'une candidature. Déclenchée dans deux
 * cas (accord du 2026-08-24 puis étendu le 2026-09-14) :
 *   - AUTOMATIQUEMENT par Admin\ApplicationController::update(), uniquement
 *     quand le nouveau statut est une DÉCISION (Retenue / NonRetenue /
 *     EnListeAttente) — comportement inchangé depuis le 2026-08-24.
 *   - MANUELLEMENT via le bouton "Envoyer un email" ajouté le 2026-09-14
 *     (Admin\ApplicationController::notify()), pour N'IMPORTE QUEL statut,
 *     y compris Nouvelle/EnCoursEtude/Preselectionnee — l'admin garde la
 *     main sur le moment de l'envoi, indépendamment d'un changement de
 *     statut. D'où le match() ci-dessous désormais exhaustif sur les 6 cas
 *     de ApplicationStatus plutôt qu'un `default`.
 *
 * subjectAndView() est `public static` pour que le contrôleur puisse
 * journaliser le sujet réellement utilisé dans application_history sans
 * dupliquer cette table de correspondance.
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

    /**
     * @return array{0: string, 1: string} [sujet, nom de la vue Blade]
     */
    public static function subjectAndView(ApplicationStatus $statut): array
    {
        return match ($statut) {
            ApplicationStatus::Nouvelle => ['Confirmation de votre candidature — Casa Impact', 'emails.applications.received'],
            ApplicationStatus::EnCoursEtude => ["Votre candidature est en cours d'étude — Casa Impact", 'emails.applications.status-en-cours-etude'],
            ApplicationStatus::Preselectionnee => ['Votre candidature a été présélectionnée — Casa Impact', 'emails.applications.status-preselectionnee'],
            ApplicationStatus::Retenue => ['Votre candidature a été retenue — Casa Impact', 'emails.applications.decided-retenue'],
            ApplicationStatus::NonRetenue => ['Suite à votre candidature — Casa Impact', 'emails.applications.decided-non-retenue'],
            ApplicationStatus::EnListeAttente => ["Votre candidature est en liste d'attente — Casa Impact", 'emails.applications.decided-en-liste-attente'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $application = $this->application->loadMissing('applicationCall');

        [$subject, $view] = self::subjectAndView($application->statut);

        return (new MailMessage)
            ->subject($subject)
            ->view($view, ['application' => $application]);
    }
}
