<?php

namespace App\Models;

use App\Enums\NewsStatus;
use App\Enums\NewsType;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;
    use HasMedia;

    protected $fillable = [
        'titre',
        'slug',
        'type',
        'corps',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'type' => NewsType::class,
            'statut' => NewsStatus::class,
        ];
    }

    /**
     * Même logique que Page/Program/ApplicationCall::generateUniqueSlug()
     * — slug dérivé du titre si absent à la création, modifiable ensuite.
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

    public function scopePublished($query)
    {
        return $query->where('statut', NewsStatus::Publie->value);
    }
}
