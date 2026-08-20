<?php

namespace App\Models;

use App\Enums\DomainStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory;

    /**
     * `slug` est volontairement ABSENT de $fillable : il est fixé une fois
     * pour toutes par DomainsSeeder et ne doit jamais être modifiable via
     * une mass-assignment, même par erreur. UpdateDomainRequest n'expose de
     * toute façon pas ce champ, mais on verrouille aussi au niveau du
     * modèle en défense en profondeur.
     */
    protected $fillable = [
        'nom',
        'description',
        'icone',
        'ordre',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'statut' => DomainStatus::class,
            'ordre' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('statut', DomainStatus::Actif->value);
    }

        /**
     * Ajouté au Module 3 (Programmes) — un domaine verrouillé peut quand
     * même être référencé par de nombreux programmes ; aucune modification
     * du schéma domains n'était nécessaire (voir architecturev1.md §C,
     * relation "1-N Program").
     */
        public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
