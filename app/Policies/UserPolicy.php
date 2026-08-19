<?php

namespace App\Policies;

use App\Models\User;

/**
 * Autorisation fine sur la gestion des comptes admin.
 * `administrateur-principal` bypass toutes ces vérifications via
 * `Gate::before` (voir AppServiceProvider::boot) — cette policy ne s'applique
 * donc en pratique qu'aux autres rôles, et sert de filet de sécurité explicite
 * même pour l'administrateur principal sur les deux règles ci-dessous qui sont
 * volontairement réaffirmées dans le contrôleur (voir UserController).
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->can('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can('users.update');
    }

    /**
     * Interdiction absolue de s'auto-supprimer, quel que soit le rôle
     * (y compris administrateur-principal — règle métier non négociable,
     * réaffirmée explicitement dans UserController::destroy() car
     * Gate::before pourrait sinon la contourner pour ce rôle).
     */
    public function delete(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        return $actor->can('users.delete');
    }
}
