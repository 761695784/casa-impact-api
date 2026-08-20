<?php

namespace App\Enums;

/**
 * Décision validée le 2026-08-20 : simple enum PHP plutôt qu'une table
 * Region/Department/Commune (marquée [DÉCISION REQUISE] dans
 * architecturev1.md §C, jamais construite). Limité aux 3 régions de la
 * zone d'intervention Casa Impact. À remplacer par un référentiel dédié
 * si un futur module (Cartographie, Contact) a besoin de Department/Commune.
 */
enum Region: string
{
    case Ziguinchor = 'ziguinchor';
    case Kolda = 'kolda';
    case Sedhiou = 'sedhiou';
}
