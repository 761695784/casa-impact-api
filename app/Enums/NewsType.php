<?php

namespace App\Enums;

enum NewsType: string
{
    case Article = 'article';
    case Annonce = 'annonce';
    case Communique = 'communique';
    case CompteRendu = 'compte-rendu';
}
