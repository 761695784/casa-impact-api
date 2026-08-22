<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

#[Group('Administration — Admin')]
class AuthController extends Controller
{
    /**
     * Convention validée : un échec de login renvoie 422 (erreur de
     * validation sur le champ `email`), pas 401 — cohérent avec le
     * comportement standard des starter kits Laravel (Breeze/Fortify) et
     * avec "enveloppe d'erreur Laravel par défaut" (§F architecturev1.md).
     */
    public function login(LoginRequest $request): UserResource
    {
        $credentials = $request->validated();

        if (! Auth::guard('web')->attempt($credentials, remember: false)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        $request->session()->regenerate();

        return new UserResource($request->user());
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * `administrateur-principal` bypass toutes les permissions via
     * Gate::before (voir AppServiceProvider), donc `getAllPermissions()`
     * de spatie renverrait une liste vide pour ce rôle s'il n'a pas
     * explicitement toutes les permissions assignées en base. On calcule
     * donc la liste complète explicitement pour ce cas — convention validée
     * "/me synthétise la liste complète des permissions pour
     * administrateur-principal" (Journal des décisions, architecturev1.md).
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $permissions = $user->hasRole('administrateur-principal')
            ? Permission::pluck('name')
            : $user->getAllPermissions()->pluck('name');

        return (new UserResource($user))->additional([
            'permissions' => $permissions,
        ]);
    }
}
