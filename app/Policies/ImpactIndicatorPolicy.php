<?php

namespace App\Policies;

use App\Models\ImpactIndicator;
use App\Models\User;

/**
 * Couvre à la fois les indicateurs (ImpactIndicator) et leurs valeurs
 * (ImpactValue) : gérer les valeurs d'un indicateur est une sous-action de
 * la gestion de l'indicateur lui-même, pas une ressource distincte du point
 * de vue des permissions (un seul groupe `impact.*`).
 */
class ImpactIndicatorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('impact.view');
    }

    public function view(User $user, ImpactIndicator $impactIndicator): bool
    {
        return $user->can('impact.view');
    }

    public function create(User $user): bool
    {
        return $user->can('impact.create');
    }

    public function update(User $user, ImpactIndicator $impactIndicator): bool
    {
        return $user->can('impact.update');
    }

    public function delete(User $user, ImpactIndicator $impactIndicator): bool
    {
        return $user->can('impact.delete');
    }
}
