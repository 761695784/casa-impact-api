<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation réelle est faite par UserPolicy::create dans le contrôleur
        // (authorize() ici resterait redondant et masquerait le vrai 403 métier).
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            // Rôles fixes et lecture seule (voir database/seeders/RolesAndPermissionsSeeder.php).
            // Un seul rôle est exigé au minimum ; le multi-rôle reste possible via
            // une mise à jour ultérieure (UpdateUserRequest) si besoin réel identifié.
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in([
                'administrateur-principal',
                'communication',
                'gestionnaire-candidatures',
            ])],
        ];
    }
}
