<?php

namespace App\Http\Requests\Admin;

use App\Enums\Region;
use App\Enums\TalentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTalentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            // Slug optionnel : généré automatiquement depuis le nom côté
            // contrôleur si absent (voir Talent::generateUniqueSlug()).
            'slug' => ['nullable', 'string', 'max:255', 'unique:talents,slug', 'alpha_dash'],
            // Pas de règle `exists:domains,id` : la table `domains` (Module 2)
            // n'est pas garantie présente dans ce lot isolé — voir migration.
            'domain_id' => ['nullable', 'integer'],
            'region' => ['nullable', Rule::enum(Region::class)],
            'presentation' => ['nullable', 'string'],
            'parcours' => ['nullable', 'string'],
            'projet' => ['nullable', 'string'],
            'realisations' => ['nullable', 'string'],
            'temoignage' => ['nullable', 'string'],
            'recit_titre' => ['nullable', 'string', 'max:255'],
            'recit_corps' => ['nullable', 'string'],
            'liens_externes' => ['nullable', 'array'],
            'liens_externes.*' => ['string', 'url', 'max:2048'],
            'statut' => ['nullable', Rule::enum(TalentStatus::class)],
        ];
    }
}
