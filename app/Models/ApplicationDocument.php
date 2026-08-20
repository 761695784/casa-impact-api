<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'type',
        'chemin',
        'nom_original',
        'mime',
        'taille',
    ];

    protected function casts(): array
    {
        return [
            'taille' => 'integer',
        ];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
