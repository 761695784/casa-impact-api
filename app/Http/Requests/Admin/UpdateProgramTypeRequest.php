<?php

namespace App\Http\Requests\Admin;

use App\Enums\DomainStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $programType = $this->route('program_type');

        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('program_types', 'slug')->ignore($programType?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'statut' => ['sometimes', Rule::enum(DomainStatus::class)],
            'ordre' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:255'],
        ];
    }
}
