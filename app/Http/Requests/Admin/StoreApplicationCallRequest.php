<?php

namespace App\Http\Requests\Admin;

use App\Enums\ApplicationCallStatus;
use App\Enums\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            // Slug optionnel : généré automatiquement depuis le titre côté
            // contrôleur si absent (voir ApplicationCall::generateUniqueSlug()).
            'slug' => ['nullable', 'string', 'max:255', 'unique:application_calls,slug', 'alpha_dash'],
            'description' => ['nullable', 'string'],
            // Aligné sur le formulaire admin (section "Informations
            // Générales") — même rôle que Program::resume.
            'resume' => ['nullable', 'string', 'max:500'],
            'objectifs' => ['nullable', 'string'],
            'public_cible' => ['nullable', 'string'],
            'region' => ['required', Rule::enum(Region::class)],
            'lieu' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'date_limite' => ['nullable', 'date'],
            'duree' => ['nullable', 'string', 'max:100'],
            'nombre_places' => ['nullable', 'integer', 'min:1'],
            'conditions' => ['nullable', 'string'],
            // Chaque document requis est désormais un objet structuré (voir
            // le formulaire admin "Documents Requis du Candidat") plutôt
            // qu'une simple chaîne — `cle` reste la valeur exploitée par
            // App\Http\Requests\Public\StoreApplicationRequest (Module 5)
            // pour vérifier les pièces jointes obligatoires.
            'documents_requis' => ['nullable', 'array'],
            'documents_requis.*.cle' => ['required', 'string', 'max:100'],
            'documents_requis.*.libelle' => ['required', 'string', 'max:255'],
            'documents_requis.*.requis' => ['sometimes', 'boolean'],
            'documents_requis.*.formats' => ['sometimes', 'array'],
            'documents_requis.*.formats.*' => ['string', 'max:20'],
            'documents_requis.*.taille_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'statut' => ['nullable', Rule::enum(ApplicationCallStatus::class)],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
        ];
    }
}
