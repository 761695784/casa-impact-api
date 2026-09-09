<?php

namespace App\Http\Requests\Admin;

use App\Enums\MediaCategorie;
use App\Enums\MediaStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Métadonnées de bibliothèque uniquement — jamais l'attachement (voir
 * AttachMediaRequest/DetachMediaRequest, endpoints dédiés) : modifier le
 * titre ou la catégorie d'une photo ne doit pas pouvoir, par effet de bord,
 * la déplacer d'une fiche à une autre.
 */
class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'nullable', 'string', 'max:255'],
            'alt' => ['sometimes', 'nullable', 'string', 'max:255'],
            'legende' => ['sometimes', 'nullable', 'string', 'max:255'],
            'categorie' => ['sometimes', Rule::enum(MediaCategorie::class)],
            'statut' => ['sometimes', Rule::enum(MediaStatus::class)],
        ];
    }
}
