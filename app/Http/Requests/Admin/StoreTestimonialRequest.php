<?php

namespace App\Http\Requests\Admin;

use App\Enums\TestimonialStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auteur' => ['required', 'string', 'max:255'],
            'role_organisation' => ['nullable', 'string', 'max:255'],
            // Plafonné à 150 caractères — accord du 2026-09-23 (les
            // témoignages affichés sur /temoignages étaient parfois de
            // longs paragraphes, cassant la mise en page des cartes).
            'citation' => ['required', 'string', 'max:150'],
            'contexte' => ['nullable', 'string'],
            // Pas de règle `exists:...,id` : les tables `programs`/
            // `application_calls` (Modules 3/4) ne sont pas garanties
            // présentes dans ce lot isolé — voir migration.
            'program_id' => ['nullable', 'integer'],
            'application_call_id' => ['nullable', 'integer'],
            'statut' => ['nullable', Rule::enum(TestimonialStatus::class)],
        ];
    }
}
