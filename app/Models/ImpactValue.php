<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un point de mesure ponctuel rattaché à un ImpactIndicator (ex. "2025/2026,
 * région Kolda, valeur 42") — sous-ressource imbriquée, jamais accédée hors
 * du contexte de son indicateur côté frontend (voir ImpactValueController).
 */
class ImpactValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'impact_indicator_id',
        'valeur',
        'periode',
        'region',
    ];

    protected $casts = [
        'valeur' => 'float',
    ];

    public function impactIndicator(): BelongsTo
    {
        return $this->belongsTo(ImpactIndicator::class);
    }
}
