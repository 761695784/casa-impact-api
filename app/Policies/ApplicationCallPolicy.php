<?php

namespace App\Policies;

use App\Models\ApplicationCall;
use App\Models\User;

class ApplicationCallPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('application-calls.view');
    }

    public function view(User $user, ApplicationCall $applicationCall): bool
    {
        return $user->can('application-calls.view');
    }

    public function create(User $user): bool
    {
        return $user->can('application-calls.create');
    }

    public function update(User $user, ApplicationCall $applicationCall): bool
    {
        return $user->can('application-calls.update');
    }

    public function delete(User $user, ApplicationCall $applicationCall): bool
    {
        return $user->can('application-calls.delete');
    }
}
