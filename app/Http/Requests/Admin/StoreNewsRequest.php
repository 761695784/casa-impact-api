<?php

namespace App\Http\Requests\Admin;

use App\Enums\NewsStatus;
use App\Enums\NewsType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            // Slug optionnel : généré automatiquement depuis le titre côté
            // contrôleur si absent (voir News::generateUniqueSlug()).
            'slug' => ['nullable', 'string', 'max:255', 'unique:news,slug', 'alpha_dash'],
            'type' => ['required', Rule::enum(NewsType::class)],
            'corps' => ['required', 'string'],
            'statut' => ['nullable', Rule::enum(NewsStatus::class)],
        ];
    }
}
