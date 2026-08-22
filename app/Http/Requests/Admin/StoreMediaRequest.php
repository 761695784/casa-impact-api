<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Alias courts et stables plutôt que le nom de classe complet —
            // évite d'exposer la structure interne de l'app et permet de
            // whitelister explicitement quels modèles peuvent recevoir des
            // médias (voir MediaController::MEDIABLE_MAP).
            'mediable_type' => ['required', Rule::in(array_keys(\App\Http\Controllers\Api\Admin\MediaController::MEDIABLE_MAP))],
            'mediable_id' => ['required', 'integer'],
            'collection' => ['nullable', 'string', 'max:100'],
            'fichier' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
            'legende' => ['nullable', 'string', 'max:255'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
