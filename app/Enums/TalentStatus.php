<?php

namespace App\Enums;

/**
 * Enum dédié à Talent, volontairement distinct de ProgramStatus/NewsStatus/
 * ApplicationCallStatus malgré des valeurs qui se recoupent conceptuellement
 * (brouillon/publie/archive) — convention du projet : chaque module de
 * contenu porte son propre enum de statut, jamais partagé entre modules.
 */
enum TalentStatus: string
{
    case Brouillon = 'brouillon';
    case Publie = 'publie';
    case Archive = 'archive';
}
