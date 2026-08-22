<?php

namespace App\Http\Requests\Admin;

use App\Enums\PartnerStatus;
use App\Enums\PartnerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartnerRequest extends FormRequest
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
            'lien' => ['sometimes', 'nullable', 'url', 'max:255'],
            'type' => ['sometimes', 'nullable', Rule::enum(PartnerType::class)],
            'statut' => ['sometimes', 'nullable', Rule::enum(PartnerStatus::class)],
            'ordre' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
