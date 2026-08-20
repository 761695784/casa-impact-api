<?php

namespace App\Http\Requests\Admin;

use App\Enums\DomainStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pas de champ `slug` ici, volontairement : voir Domain model et migration
 * pour le raisonnement (référentiel fixe, slug figé après le seed).
 */
class UpdateDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'icone' => ['sometimes', 'nullable', 'string', 'max:100'],
            'ordre' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'statut' => ['sometimes', Rule::enum(DomainStatus::class)],
        ];
    }
}
