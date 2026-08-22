<?php

namespace App\Http\Requests\Admin;

use App\Enums\PartnerStatus;
use App\Enums\PartnerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'lien' => ['nullable', 'url', 'max:255'],
            'type' => ['nullable', Rule::enum(PartnerType::class)],
            'statut' => ['nullable', Rule::enum(PartnerStatus::class)],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
