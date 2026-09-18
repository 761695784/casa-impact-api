<?php

namespace App\Models;

use App\Enums\ContributionDomain;
use App\Enums\ContributionType;
use App\Enums\MembershipRegion;
use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
            'whatsapp_invite_used_at' => 'datetime',
        ];
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Jeton du lien WhatsApp à usage unique (accord du 2026-09-18) —
     * volontairement PAS dans $fillable : comme `numero_membre`/`statut`,
     * ce champ est toujours fixé par le serveur, jamais par une entrée
     * utilisateur. Génère le jeton au premier appel (typiquement au moment
     * de l'envoi de MembershipValidated) et le réutilise ensuite tel quel
     * — un renvoi de l'email de validation ne doit jamais changer le lien
     * déjà éventuellement cliqué par l'adhérent.
     */
    public function ensureWhatsappInviteToken(): string
    {
        if ($this->whatsapp_invite_token) {
            return $this->whatsapp_invite_token;
        }

        $token = (string) Str::uuid();
        $this->forceFill(['whatsapp_invite_token' => $token])->save();

        return $token;
    }
}
