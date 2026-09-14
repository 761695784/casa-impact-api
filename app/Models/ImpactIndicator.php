<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Aligné sur la ressource réelle attendue par le frontend (types/models.ts) :
 * volontairement PAS de `categorie`, `ordre`, `statut`, `cible`,
 * `domaine_id`/`programme_id` — un indicateur d'impact est simple (libellé +
 * unité + description) et toujours public dès sa création (pas de brouillon,
 * voir ImpactIndicatorController).
 */
class ImpactIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
        'unite',
        'description',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(ImpactValue::class);
    }
}
