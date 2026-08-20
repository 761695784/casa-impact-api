<?php

namespace App\Models;

use App\Enums\PageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'slug',
        'corps',
        'meta_description',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'statut' => PageStatus::class,
        ];
    }

    /**
     * Génère un slug unique à partir du titre si aucun slug n'est fourni
     * explicitement. Appelé depuis PageController::store() — pas un
     * événement de modèle global, pour rester explicite et prévisible
     * plutôt que "magique" (cohérent avec la préférence du projet pour des
     * contrôleurs qui orchestrent clairement, voir architecturev1.md §B).
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
        return $query->where('statut', PageStatus::Publie->value);
    }
}
