<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'slug',
        'description',
        'statut',
        'domain_id',
        'program_type_id',
        'date_debut',
        'date_fin',
    ];

    protected function casts(): array
    {
        return [
            'statut' => ProgramStatus::class,
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    /**
     * Même logique que Page::generateUniqueSlug() — slug dérivé du titre si
     * absent à la création, modifiable ensuite via update explicite.
     */
    public static function generateUniqueSlug(string $titre, ?int $ignoreId = null): string
    {
        $base = Str::slug($titre);
        $slug = $base;
        $i = 1;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function programType()
    {
        return $this->belongsTo(ProgramType::class);
    }

    public function scopePublished($query)
    {
        return $query->where('statut', ProgramStatus::Publie->value);
    }
}
