<?php

namespace App\Enums;

enum PageStatus: string
{
    case Brouillon = 'brouillon';
    case Publie = 'publie';
    case Archive = 'archive';
}
