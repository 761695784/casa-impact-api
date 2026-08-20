<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Table technique interne — jamais exposée par une Resource/route. Sert
 * uniquement de verrou pour ApplicationReferenceGenerator.
 */
class ApplicationReferenceCounter extends Model
{
    protected $fillable = [
        'annee',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'annee' => 'integer',
            'sequence' => 'integer',
        ];
    }
}
