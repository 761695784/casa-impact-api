<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\User $target */
        $target = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($target?->id),
            ],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'roles' => ['sometimes', 'required', 'array', 'min:1'],
            'roles.*' => [Rule::in([
                'administrateur-principal',
                'communication',
                'gestionnaire-candidatures',
            ])],
        ];
    }

    /**
     * Règle métier non contournable : personne ne peut modifier son propre
     * rôle, y compris l'administrateur-principal (évite qu'un admin se
     * retrouve accidentellement — ou volontairement — sans le bon niveau
     * d'accès). Vérifiée ici en plus du contrôleur pour renvoyer une erreur
     * de validation 422 explicite plutôt qu'un 403 générique.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $target = $this->route('user');

            if ($this->filled('roles') && $this->user()?->is($target)) {
                $validator->errors()->add('roles', "Vous ne pouvez pas modifier votre propre rôle.");
            }
        });
    }
}
