<?php

namespace App\Enums;

/**
 * Enum dédié à Testimonial, volontairement distinct de TalentStatus/
 * NewsStatus/ProgramStatus/ApplicationCallStatus malgré des valeurs qui se
 * recoupent conceptuellement (brouillon/publie/archive) — convention du
 * projet : chaque module de contenu porte son propre enum de statut, jamais
 * partagé entre modules.
 */
enum TestimonialStatus: string
{
    case Brouillon = 'brouillon';
    case Publie = 'publie';
    case Archive = 'archive';
}
