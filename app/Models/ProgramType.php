<?php

namespace App\Models;

use App\Enums\DomainStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Contrairement à Domain, ProgramType n'est PAS un référentiel verrouillé :
 * aucune liste officielle et fermée n'existe dans le brief métier (décision
 * validée le 2026-08-19) — CRUD complet exposé côté admin.
 *
 * `statut`/`ordre` : l'admin désactive un type (actif/inactif, même enum
 * que Domain) plutôt que de le supprimer par défaut — la suppression
 * définitive (`destroy()`, avec 409 si le type est encore utilisé par un
 * programme) reste une action distincte, toujours disponible en parallèle.
 */
class ProgramType extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'slug',
        'description',
        'statut',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'statut' => DomainStatus::class,
            'ordre' => 'integer',
        ];
    }

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
