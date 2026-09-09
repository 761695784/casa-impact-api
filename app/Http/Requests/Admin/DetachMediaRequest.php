<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Api\Admin\MediaController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetachMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mediable_type' => ['required', Rule::in(array_keys(MediaController::MEDIABLE_MAP))],
            'mediable_id' => ['required', 'integer'],
            // Omise : détache toutes les collections de cette fiche pour ce
            // média. Fournie : ne détache que cette collection précise
            // (ex. retirer d'un album "gallery" sans toucher un usage
            // "cover" éventuel de la même photo sur la même fiche).
            'collection' => ['nullable', 'string', 'max:100'],
        ];
    }
}
