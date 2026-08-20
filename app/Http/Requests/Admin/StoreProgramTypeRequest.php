<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            // Slug optionnel : généré automatiquement depuis le nom côté
            // contrôleur si absent (voir ProgramType::generateUniqueSlug()).
            'slug' => ['nullable', 'string', 'max:255', 'unique:program_types,slug', 'alpha_dash'],
            'description' => ['nullable', 'string'],
        ];
    }
}
