<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

/**
 * Pas de méthode create() : un message de contact n'est jamais "créé" par un
 * admin — uniquement soumis via le formulaire public
 * (Public\ContactMessageController::store(), non protégé par une policy
 * puisqu'aucune authentification n'y est requise).
 */
class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contact-messages.view');
    }

    public function view(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contact-messages.view');
    }

    public function update(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contact-messages.update');
    }

    public function delete(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('contact-messages.delete');
    }
}
