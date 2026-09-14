<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email libre composé depuis la partie admin (section Messages, bouton
 * "Envoyer un message" — accord du 2026-09-11). Réutilise le layout
 * `emails.layout` déjà utilisé par MembershipReceived / MembershipValidated
 * (logo, filigrane, couleurs, signature) : voir resources/views/emails/admin-message.blade.php.
 *
 * $bodyHtml est du HTML déjà échappé/construit côté contrôleur (voir
 * AdminMessageController::send()) à partir du texte brut saisi par
 * l'admin — jamais du HTML brut venant directement de la requête.
 */
class AdminBroadcastMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-message',
            with: [
                'subject' => $this->subjectLine,
                'bodyHtml' => $this->bodyHtml,
            ],
        );
    }
}
