<?php

namespace App\Http\Requests\Admin;

use App\Enums\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImpactValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valeur' => ['sometimes', 'required', 'numeric'],
            'periode' => ['sometimes', 'required', 'string', 'max:50'],
            'region' => ['sometimes', 'nullable', Rule::enum(Region::class)],
        ];
    }
}
