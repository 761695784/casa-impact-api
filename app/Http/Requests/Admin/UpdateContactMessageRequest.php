<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContactMessageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Volontairement le SEUL champ modifiable par l'admin est `statut` — les
 * données saisies par l'expéditeur (identité, message...) ne sont jamais
 * éditables depuis l'admin.
 */
class UpdateContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::enum(ContactMessageStatus::class)],
        ];
    }
}
