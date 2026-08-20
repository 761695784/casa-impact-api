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
            'documents_requis.*' => ['string', 'max:100'],
            'statut' => ['sometimes', Rule::enum(ApplicationCallStatus::class)],
            'program_id' => ['sometimes', 'required', 'integer', 'exists:programs,id'],
        ];
    }
}
