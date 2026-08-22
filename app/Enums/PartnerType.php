<?php

namespace App\Enums;

/**
 * Type de partenaire (Module 11). Volontairement un enum dédié, distinct de
 * tout autre enum du projet même si certaines valeurs se recoupent
 * conceptuellement avec d'autres modules — convention du projet : pas de
 * partage d'enum entre modules.
 */
enum PartnerType: string
{
    case Institutionnel = 'institutionnel';
    case Financier = 'financier';
    case Technique = 'technique';
    case Media = 'media';
}
