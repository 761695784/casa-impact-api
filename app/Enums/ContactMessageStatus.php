<?php

namespace App\Enums;

/**
 * Cycle de vie d'un message de contact (Module 16) — un message part
 * toujours `nouveau` (forcé côté Public\ContactMessageController::store(),
 * quelle que soit la donnée envoyée par le client) et passe à `traite`
 * lorsqu'un membre de l'équipe l'a pris en charge.
 */
enum ContactMessageStatus: string
{
    case Nouveau = 'nouveau';
    case Traite = 'traite';
}
