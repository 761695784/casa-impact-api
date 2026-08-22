<?php

namespace App\Enums;

/**
 * Statut de publication d'un partenaire (Module 11). Terminologie propre à
 * ce module — "actif/inactif", pas "publié/brouillon" comme les modules de
 * contenu éditorial (News/Program/...).
 */
enum PartnerStatus: string
{
    case Actif = 'actif';
    case Inactif = 'inactif';
}
