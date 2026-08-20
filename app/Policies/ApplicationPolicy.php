<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

/**
 * Pas de méthode create() : les candidatures ne sont jamais créées côté
 * admin dans cette version — uniquement via la soumission publique
 * (Public\ApplicationController::store(), non protégée par une policy
 * puisqu'aucune authentification n'y est requise).
 */
class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('applications.view');
    }

    public function view(User $user, Application $application): bool
    {
        return $user->can('applications.view');
    }

    public function update(User $user, Application $application): bool
    {
        return $user->can('applications.update');
    }

    public function delete(User $user, Application $application): bool
    {
        return $user->can('applications.delete');
    }
}
