<?php

namespace App\Enums;

enum ProgramStatus: string
{
    case Brouillon = 'brouillon';
    case Publie = 'publie';
    case Archive = 'archive';
}
