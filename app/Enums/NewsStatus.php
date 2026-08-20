<?php

namespace App\Enums;

enum NewsStatus: string
{
    case Brouillon = 'brouillon';
    // Statut interne de relecture, jamais visible côté public (voir
    // News::scopePublished()) — permet à l'équipe de valider une actualité
    // avant sa mise en ligne effective.
    case Previsualisation = 'previsualisation';
    case Publie = 'publie';
    case Archive = 'archive';
}
