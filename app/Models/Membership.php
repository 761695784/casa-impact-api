<?php

namespace App\Models;

use App\Enums\ContributionDomain;
use App\Enums\ContributionType;
use App\Enums\MembershipRegion;
use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * `numero_membre` et `statut` sont TOUJOURS fixés par le serveur
 * (MembershipReferenceGenerator / MembershipController), jamais par une
 * entrée utilisateur directe — StoreMembershipRequest (public) ne valide
 * d'ailleurs ni l'un ni l'autre. Même principe que Application::$fillable.
 */
class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_membre',
        'nom_complet',
        'email',
        'telephone',
        'profession',
        'region',
        'departement',
        'domaine_contribution',
        'type_contribution',
        'photo_path',
        'engagement_moral',
        'suggestions_competences',
        'statut',
        'source',
        'admin_note',
        'validated_at',
        'validated_by',
    ];

    protected function casts(): array
    {
        return [
            'region' => MembershipRegion::class,
            'domaine_contribution' => ContributionDomain::class,
            'type_contribution' => ContributionType::class,
            'statut' => MembershipStatus::class,
            'engagement_moral' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
