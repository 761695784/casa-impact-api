<?php

namespace App\Http\Requests\Public;

use App\Enums\ContributionDomain;
use App\Enums\ContributionType;
use App\Enums\MembershipRegion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Champs directement dérivés du formulaire Google Forms officiel
 * ("Formulaire d'adhésion à CASA IMPACT", capture fournie le 2026-08-24) :
 * email, prénom(s)+nom, téléphone/WhatsApp, profession (optionnel), région
 * de résidence, département en Casamance (requis seulement pour
 * Ziguinchor/Sédhiou/Kolda), domaine de contribution (optionnel), type de
 * contribution (optionnel), photo (requise), engagement moral (requis,
 * case unique), suggestions/compétences (optionnel).
 *
 * `numero_membre` et `statut` ne sont PAS validés ici : toujours fixés par
 * le serveur (voir Public\MembershipController::store()), jamais par une
 * entrée utilisateur directe — même principe que StoreApplicationRequest.
 */
class StoreMembershipRequest extends FormRequest
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
            // Le formulaire officiel affiche ce champ comme obligatoire,
            // mais il n'a de sens que pour un adhérent résidant en
            // Casamance — un adhérent de Dakar ou de la diaspora n'a pas
            // de département en Casamance à renseigner (voir
            // MembershipRegion::estEnCasamance()).
            'departement' => [
                Rule::requiredIf(function () {
                    $region = MembershipRegion::tryFrom((string) $this->input('region'));

                    return $region && $region->estEnCasamance();
                }),
                'nullable', 'string', 'max:255',
            ],
            'domaine_contribution' => ['nullable', Rule::enum(ContributionDomain::class)],
            'type_contribution' => ['nullable', Rule::enum(ContributionType::class)],
            // 10 Mo max — reprend exactement la limite affichée sur le
            // formulaire Google Forms officiel ("Importez 1 fichier
            // compatible : image. 10 MB max.").
            'photo' => ['required', 'image', 'max:10240'],
            'engagement_moral' => ['required', 'accepted'],
            'suggestions_competences' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
