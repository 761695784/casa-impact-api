<?php

namespace App\Models;

use App\Enums\Region;
use App\Enums\TalentStatus;
use App\Traits\HasLocation;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Talent extends Model
{
    use HasFactory;
    use HasMedia;
    use HasLocation;

    /**
     * Déclaré explicitement suite à un bug constaté le 2026-08-22 : la
     * convention de nommage automatique d'Eloquent (pluriel de "Talent")
     * résolvait vers la table "talent" (singulier) plutôt que "talents"
     * dans l'environnement de test de l'utilisateur — cause encore
     * incertaine (version de la librairie d'inflexion utilisée par
     * Illuminate\Support\Str::plural), mais ce n'est de toute façon jamais
     * une bonne pratique de dépendre de cette magie. Fixé en dur pour
     * lever toute ambiguïté, quelle que soit la cause exacte.
     */
    protected $table = 'talents';

    protected $fillable = [
        'nom',
        'slug',
        'domain_id',
        'region',
        'presentation',
        'parcours',
        'projet',
        'realisations',
        'temoignage',
        'recit_titre',
        'recit_corps',
        'liens_externes',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'region' => Region::class,
            'liens_externes' => 'array',
            'statut' => TalentStatus::class,
        ];
    }

    /**
     * Même logique que Page/Program/ApplicationCall::generateUniqueSlug() —
     * slug dérivé du nom si absent à la création, modifiable ensuite via
     * update explicite.
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

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function scopePublished($query)
    {
        return $query->where('statut', TalentStatus::Publie->value);
    }
}
