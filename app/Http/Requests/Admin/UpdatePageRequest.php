<?php

namespace App\Http\Requests\Admin;

use App\Enums\PageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $page = $this->route('page');

        return [
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('pages', 'slug')->ignore($page?->id),
            ],
            'corps' => ['sometimes', 'required', 'string'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'statut' => ['sometimes', Rule::enum(PageStatus::class)],
        ];
    }
}
