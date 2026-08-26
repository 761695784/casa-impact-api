<?php

namespace App\Http\Requests\Admin;

use App\Enums\MembershipStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Comme UpdateApplicationRequest : seuls `statut` et `admin_note` sont
 * modifiables depuis l'admin — les données déclarées par l'adhérent
 * (identité, coordonnées, choix de contribution...) ne sont jamais
 * éditées ici. Le passage à `validee` déclenche la génération de la
 * carte + l'envoi de MembershipValidated (voir
 * Admin\MembershipController::update()).
 */
class UpdateMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::enum(MembershipStatus::class)],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
