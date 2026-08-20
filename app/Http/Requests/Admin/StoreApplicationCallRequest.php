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
            'documents_requis' => ['nullable', 'array'],
            'documents_requis.*' => ['string', 'max:100'],
            'statut' => ['nullable', Rule::enum(ApplicationCallStatus::class)],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
        ];
    }
}
