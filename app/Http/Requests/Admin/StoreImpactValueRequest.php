<?php

namespace App\Http\Requests\Admin;

use App\Enums\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImpactValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valeur' => ['required', 'numeric'],
            'periode' => ['required', 'string', 'max:50'],
            'region' => ['nullable', Rule::enum(Region::class)],
        ];
    }
}
