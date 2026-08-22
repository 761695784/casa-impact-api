<?php

namespace App\Http\Requests\Admin;

use App\Enums\Region;
use App\Enums\TalentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTalentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $talent = $this->route('talent');

        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('talents', 'slug')->ignore($talent?->id),
            ],
            'domain_id' => ['sometimes', 'nullable', 'integer'],
            'region' => ['sometimes', 'nullable', Rule::enum(Region::class)],
            'presentation' => ['sometimes', 'nullable', 'string'],
            'parcours' => ['sometimes', 'nullable', 'string'],
            'projet' => ['sometimes', 'nullable', 'string'],
            'realisations' => ['sometimes', 'nullable', 'string'],
            'temoignage' => ['sometimes', 'nullable', 'string'],
            'recit_titre' => ['sometimes', 'nullable', 'string', 'max:255'],
            'recit_corps' => ['sometimes', 'nullable', 'string'],
            'liens_externes' => ['sometimes', 'nullable', 'array'],
            'liens_externes.*' => ['string', 'url', 'max:2048'],
            'statut' => ['sometimes', Rule::enum(TalentStatus::class)],
        ];
    }
}
