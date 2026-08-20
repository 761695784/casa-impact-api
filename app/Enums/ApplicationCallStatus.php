<?php

namespace App\Enums;

enum ApplicationCallStatus: string
{
    case Brouillon = 'brouillon';
    case Publie = 'publie';
    case Ferme = 'ferme';
}
