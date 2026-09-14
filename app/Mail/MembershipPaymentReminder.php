<?php

namespace App\Mail;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Rappel de paiement envoyé manuellement par l'admin (bouton "Envoyer un
 * rappel de paiement" de la page Adhésions — accord du 2026-09-11) aux
 * membres dont l'adhésion est encore `en_attente_paiement`. Reprend les
 * mêmes instructions de paiement Wave que l'email de confirmation initial
 * (MembershipReceived), pour les membres qui ne l'auraient pas retrouvé.
 */
class MembershipPaymentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Membership $membership)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rappel — Finalisez votre adhésion à Casa Impact',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.memberships.payment-reminder',
            with: ['membership' => $this->membership],
        );
    }
}
