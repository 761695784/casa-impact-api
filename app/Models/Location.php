<?php

namespace App\Models;

use App\Enums\Region;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    // Déclaré explicitement par précaution suite au bug de pluralisation
    // constaté sur Talent (voir Talent::$table).
    protected $table = 'locations';

    protected $fillable = [
        'locatable_type',
        'locatable_id',
        'latitude',
        'longitude',
        'libelle',
        'region',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'region' => Region::class,
        ];
    }

    public function locatable()
    {
        return $this->morphTo();
    }
}
