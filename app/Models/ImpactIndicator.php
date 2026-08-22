<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Module 13 (Impact) — indicateur d'impact générique (ex. "Jeunes formés",
 * "Emplois créés"), sans statut brouillon/publié : un indicateur créé par
 * l'administration est immédiatement visible côté public (architecturev1.md
 * §I ne prévoit pas de cycle de publication ici, contrairement aux modules
 * de contenu éditorial).
 */
class ImpactIndicator extends Model
{
    use HasFactory;

    // Déclaré explicitement par précaution suite au bug de pluralisation
    // constaté sur Talent (voir Talent::$table).
    protected $table = 'impact_indicators';

    protected $fillable = [
        'libelle',
        'unite',
        'description',
    ];

    public function values()
    {
        return $this->hasMany(ImpactValue::class);
    }
}
