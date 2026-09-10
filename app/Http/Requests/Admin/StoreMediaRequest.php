<?php

namespace App\Http\Requests\Admin;

use App\Enums\MediaCategorie;
use App\Http\Controllers\Api\Admin\MediaController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `mediable_type`/`mediable_id`/`collection`/`ordre` sont désormais
 * optionnels (voir MediaController::store()) : un upload peut alimenter
 * uniquement la bibliothèque (médiathèque), sans être immédiatement
 * rattaché à une fiche — l'attachement se fait alors séparément via
 * POST /api/admin/media/{media}/attach. Si `mediable_type` est fourni,
 * `mediable_id` devient obligatoire (et inversement) : pas d'attachement
 * à moitié spécifié.
 */
class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fichier' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
            'nom' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'legende' => ['nullable', 'string', 'max:255'],
            'categorie' => ['nullable', Rule::enum(MediaCategorie::class)],
            'mediable_type' => ['nullable', 'required_with:mediable_id', Rule::in(array_keys(MediaController::MEDIABLE_MAP))],
            'mediable_id' => ['nullable', 'required_with:mediable_type', 'integer'],
            'collection' => ['nullable', 'string', 'max:100'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
