<?php

namespace App\Http\Requests\Admin;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Volontairement le SEUL champ modifiable par l'admin est `statut` — les
 * données saisies par le candidat (identité, profil, motivation...) ne sont
 * jamais éditables depuis l'admin, cohérent avec le principe de ne pas
 * altérer une soumission déclarative.
 */
class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::enum(ApplicationStatus::class)],
        ];
    }
}
