<?php

namespace App\Http\Requests\Admin;

use App\Enums\TestimonialStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auteur' => ['sometimes', 'required', 'string', 'max:255'],
            'role_organisation' => ['sometimes', 'nullable', 'string', 'max:255'],
            // Plafonné à 150 caractères — voir StoreTestimonialRequest.
            'citation' => ['sometimes', 'required', 'string', 'max:150'],
            'contexte' => ['sometimes', 'nullable', 'string'],
            'program_id' => ['sometimes', 'nullable', 'integer'],
            'application_call_id' => ['sometimes', 'nullable', 'integer'],
            'statut' => ['sometimes', Rule::enum(TestimonialStatus::class)],
        ];
    }
}
