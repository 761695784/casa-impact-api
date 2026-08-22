<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\RoleResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * Lecture seule : les rôles sont fixes, seedés par
 * database/seeders/RolesAndPermissionsSeeder.php. Aucune route
 * store/update/destroy n'existe volontairement (décision reprise du
 * projet précédent, voir architecturev1.md).
 */
#[Group('Administration — Admin')]
class RoleController extends Controller
{
    public function index(Request $request)
    {
        // Vérification de permission "à plat" (pas de Policy pour le modèle
        // Role de spatie) : Gate::before (spatie) intercepte cette ability
        // et vérifie directement `roles.view` sur l'utilisateur courant.
        $this->authorize('roles.view');

        return RoleResource::collection(
            Role::with('permissions')->orderBy('name')->get()
        );
    }
}
