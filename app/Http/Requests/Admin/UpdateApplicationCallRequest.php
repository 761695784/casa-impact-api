<?php

namespace App\Http\Requests\Admin;

use App\Enums\ApplicationCallStatus;
use App\Enums\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $applicationCall = $this->route('application_call');

        return [
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('application_calls', 'slug')->ignore($applicationCall?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'resume' => ['sometimes', 'nullable', 'string', 'max:500'],
            'objectifs' => ['sometimes', 'nullable', 'string'],
            'public_cible' => ['sometimes', 'nullable', 'string'],
            'region' => ['sometimes', Rule::enum(Region::class)],
            'lieu' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_debut' => ['sometimes', 'nullable', 'date'],
            'date_fin' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_debut'],
            'date_limite' => ['sometimes', 'nullable', 'date'],
            'duree' => ['sometimes', 'nullable', 'string', 'max:100'],
            'nombre_places' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'conditions' => ['sometimes', 'nullable', 'string'],
            'documents_requis' => ['sometimes', 'nullable', 'array'],
            'documents_requis.*.cle' => ['required', 'string', 'max:100'],
            'documents_requis.*.libelle' => ['required', 'string', 'max:255'],
            'documents_requis.*.requis' => ['sometimes', 'boolean'],
            'documents_requis.*.formats' => ['sometimes', 'array'],
            'documents_requis.*.formats.*' => ['string', 'max:20'],
            'documents_requis.*.taille_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'statut' => ['sometimes', Rule::enum(ApplicationCallStatus::class)],
            'program_id' => ['sometimes', 'required', 'integer', 'exists:programs,id'],
        ];
    }
}
