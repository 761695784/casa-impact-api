<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;

/**
 * Volontairement pas de méthode create()/delete() : les domaines sont un
 * référentiel fixe (6 entrées seedées par DomainsSeeder). Aucune route
 * store/destroy n'existe côté DomainController — cette policy ne couvre que
 * ce qui est réellement exposé (lecture-seule + update de contenu).
 */
class DomainPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domains.view');
    }

    public function view(User $user, Domain $domain): bool
    {
        return $user->can('domains.view');
    }

    public function update(User $user, Domain $domain): bool
    {
        return $user->can('domains.update');
    }
}
