<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Table technique interne — jamais exposée par une Resource/route. Sert
 * uniquement de verrou pour MembershipReferenceGenerator. Compteur
 * INDÉPENDANT de ApplicationReferenceCounter (décision explicite du
 * 2026-08-24 : candidatures et adhésions ne partagent pas la même
 * séquence, bien que le format de référence produit soit identique).
 */
class MembershipReferenceCounter extends Model
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
