<?php

namespace App\Models;

use App\Enums\Region;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpactValue extends Model
{
    use HasFactory;

    // Déclaré explicitement par précaution suite au bug de pluralisation
    // constaté sur Talent (voir Talent::$table).
    protected $table = 'impact_values';

    protected $fillable = [
        'impact_indicator_id',
        'valeur',
        'periode',
        'region',
    ];

    protected function casts(): array
    {
        return [
            'valeur' => 'decimal:2',
            'region' => Region::class,
        ];
    }

    public function impactIndicator()
    {
        return $this->belongsTo(ImpactIndicator::class);
    }
}
