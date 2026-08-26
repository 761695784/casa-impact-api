<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;

/**
 * `memberships.*` volontairement assigné au rôle `administrateur-principal`
 * SEULEMENT dans RolesAndPermissionsSeeder (ni `communication`, ni
 * `gestionnaire-candidatures`) — la validation d'une adhésion implique de
 * vérifier un VRAI paiement (capture Wave envoyée sur WhatsApp), une
 * responsabilité plus sensible qu'une simple gestion de contenu éditorial
 * ou de candidatures. Décision à ajuster facilement si l'association
 * souhaite l'ouvrir à un autre rôle : il suffit de modifier le seeder.
 */
class MembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('memberships.view');
    }

    public function view(User $user, Membership $membership): bool
    {
        return $user->can('memberships.view');
    }

    public function create(User $user): bool
    {
        return $user->can('memberships.create');
    }

    public function update(User $user, Membership $membership): bool
    {
        return $user->can('memberships.update');
    }

    public function delete(User $user, Membership $membership): bool
    {
        return $user->can('memberships.delete');
    }
}
