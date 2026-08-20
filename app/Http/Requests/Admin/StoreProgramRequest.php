<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProgramStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
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
            // contrôleur si absent (voir Program::generateUniqueSlug()).
            'slug' => ['nullable', 'string', 'max:255', 'unique:programs,slug', 'alpha_dash'],
            'description' => ['nullable', 'string'],
            'statut' => ['nullable', Rule::enum(ProgramStatus::class)],
            'domain_id' => ['required', 'integer', 'exists:domains,id'],
            'program_type_id' => ['required', 'integer', 'exists:program_types,id'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ];
    }
}
