<?php

namespace App\Http\Requests\Admin;

use App\Enums\Region;
use App\Http\Controllers\Api\Admin\LocationController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locatable_type' => ['required', Rule::in(array_keys(LocationController::LOCATABLE_MAP))],
            'locatable_id' => ['required', 'integer'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'libelle' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', Rule::enum(Region::class)],
        ];
    }
}
