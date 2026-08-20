<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\Region;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * `reference` et `statut` sont TOUJOURS fixés par le serveur
 * (ApplicationReferenceGenerator / ApplicationCapacityChecker), jamais par
 * une entrée utilisateur directe — StoreApplicationRequest (public) ne
 * valide d'ailleurs ni l'un ni l'autre. Les avoir dans $fillable ne pose donc
 * pas de risque de mass-assignment : seul du code serveur de confiance les
 * peuple.
 */
class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'application_call_id',
        'nom',
        'prenom',
        'email',
        'telephone',
        'region',
        'lieu',
        'tranche_age',
        'niveau_etudes',
        'situation_professionnelle',
        'competences',
        'experience',
        'motivation',
        'projet',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'statut' => ApplicationStatus::class,
            'region' => Region::class,
        ];
    }

    public function applicationCall()
    {
        return $this->belongsTo(ApplicationCall::class);
    }

    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    /**
     * "Compte" contre le quota nombre_places de l'appel — une candidature
     * déjà en liste d'attente n'occupe pas de place (voir
     * ApplicationCapacityChecker).
     */
    public function scopeCountedTowardsCapacity($query)
    {
        return $query->where('statut', '!=', ApplicationStatus::EnListeAttente->value);
    }
}
