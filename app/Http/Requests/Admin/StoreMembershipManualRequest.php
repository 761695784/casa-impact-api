<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContributionDomain;
use App\Enums\ContributionType;
use App\Enums\MembershipRegion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Saisie manuelle par l'admin, pour les membres ayant adhéré AVANT la mise
 * en ligne du site (accord explicite de l'utilisateur le 2026-08-24). Deux
 * différences avec StoreMembershipRequest (formulaire public) :
 *   - `send_welcome_email` (bool, défaut false) : permet à l'admin de NE
 *     PAS envoyer le mail "adhésion reçue aujourd'hui" pour un adhérent
 *     historique — voir Admin\MembershipController::store().
 *   - la photo reste requise (la carte de membre en a besoin), mais peut
 *     aussi être un fichier déjà en main de l'admin plutôt qu'un import
 *     utilisateur — pas de différence de validation, seulement de
 *     provenance.
 * Statut : toujours créé à `validee` directement (voir contrôleur), un
 * membre saisi manuellement n'a pas de cycle "en attente de paiement".
 */
class StoreMembershipManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_complet' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['required', 'string', 'max:30'],
            'profession' => ['nullable', 'string', 'max:255'],
            'region' => ['required', Rule::enum(MembershipRegion::class)],
            'departement' => [
                Rule::requiredIf(function () {
                    $region = MembershipRegion::tryFrom((string) $this->input('region'));

                    return $region && $region->estEnCasamance();
                }),
                'nullable', 'string', 'max:255',
            ],
            'domaine_contribution' => ['nullable', Rule::enum(ContributionDomain::class)],
            'type_contribution' => ['nullable', Rule::enum(ContributionType::class)],
            'photo' => ['required', 'image', 'max:10240'],
            'suggestions_competences' => ['nullable', 'string', 'max:5000'],
            'admin_note' => ['nullable', 'string', 'max:5000'],
            'send_welcome_email' => ['sometimes', 'boolean'],
        ];
    }
}
