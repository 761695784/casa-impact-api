<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'collection',
        'fichier',
        'nom_original',
        'mime',
        'taille',
        'legende',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'taille' => 'integer',
            'ordre' => 'integer',
        ];
    }

    public function mediable()
    {
        return $this->morphTo();
    }
}
