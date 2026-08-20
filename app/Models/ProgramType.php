<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Contrairement à Domain, ProgramType n'est PAS un référentiel verrouillé :
 * aucune liste officielle et fermée n'existe dans le brief métier (décision
 * validée le 2026-08-19) — CRUD complet exposé côté admin.
 */
class ProgramType extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'slug',
        'description',
    ];

    /**
     * Même logique que Page::generateUniqueSlug() : slug dérivé du nom si
     * absent à la création, modifiable librement ensuite (pas de contrainte
     * de stabilité comme pour Domain).
     */
    public static function generateUniqueSlug(string $nom, ?int $ignoreId = null): string
    {
        $base = Str::slug($nom);
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

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
