<?php

namespace App\Http\Requests\Admin;

use App\Enums\PageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
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
            // contrôleur si absent (voir Page::generateUniqueSlug()).
            'slug' => ['nullable', 'string', 'max:255', 'unique:pages,slug', 'alpha_dash'],
            'corps' => ['required', 'string'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'statut' => ['nullable', Rule::enum(PageStatus::class)],
        ];
    }
}
