<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProgramStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $program = $this->route('program');

        return [
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('programs', 'slug')->ignore($program?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'statut' => ['sometimes', Rule::enum(ProgramStatus::class)],
            'domain_id' => ['sometimes', 'required', 'integer', 'exists:domains,id'],
            'program_type_id' => ['sometimes', 'required', 'integer', 'exists:program_types,id'],
            'date_debut' => ['sometimes', 'nullable', 'date'],
            'date_fin' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_debut'],
        ];
    }
}
