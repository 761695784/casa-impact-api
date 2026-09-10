<?php

namespace App\Models;

use App\Enums\ApplicationCallStatus;
use App\Enums\Region;
use App\Traits\HasLocation;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApplicationCall extends Model
{
    use HasFactory;
    use HasMedia;
    use HasLocation;

    protected $fillable = [
        'titre',
        'slug',
        'description',
        // Résumé accrocheur affiché dans les cartes/aperçus admin et sur la
        // page publique — ajouté pour aligner le backend sur le formulaire
        // admin déjà conçu côté frontend (même logique que Program::resume).
        'resume',
        'objectifs',
        'public_cible',
        'region',
        'lieu',
        'date_debut',
        'date_fin',
        'date_limite',
        'duree',
        'nombre_places',
        'conditions',
        'documents_requis',
        'statut',
        'program_id',
    ];

    protected function casts(): array
    {
        return [
            'statut' => ApplicationCallStatus::class,
            'region' => Region::class,
            'date_debut' => 'date',
            'date_fin' => 'date',
            'date_limite' => 'date',
            'nombre_places' => 'integer',
            // Depuis l'alignement avec le formulaire admin, chaque entrée
            // est un objet {cle, libelle, requis, formats?, taille_max?}
            // plutôt qu'une simple chaîne — voir StoreApplicationCallRequest
            // et App\Http\Requests\Public\StoreApplicationRequest (Module 5)
            // qui lit désormais `cle`/`requis` sur chaque entrée.
            'documents_requis' => 'array',
        ];
    }

    /**
     * Même logique que Page/Program::generateUniqueSlug() — slug dérivé du
     * titre si absent à la création, modifiable ensuite via update explicite.
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

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function scopePublished($query)
    {
        return $query->where('statut', ApplicationCallStatus::Publie->value);
    }

    /**
     * Ajouté au Module 5 (Candidatures) — architecturev1.md §C, relation
     * "1-N Application". Aucune modification du schéma `application_calls`
     * n'était nécessaire.
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
