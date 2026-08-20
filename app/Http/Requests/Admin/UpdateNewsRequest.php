<?php

namespace App\Http\Requests\Admin;

use App\Enums\NewsStatus;
use App\Enums\NewsType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $news = $this->route('news');

        return [
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('news', 'slug')->ignore($news?->id),
            ],
            'type' => ['sometimes', Rule::enum(NewsType::class)],
            'corps' => ['sometimes', 'required', 'string'],
            'statut' => ['sometimes', Rule::enum(NewsStatus::class)],
        ];
    }
}
