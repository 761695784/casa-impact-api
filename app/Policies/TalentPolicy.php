<?php

namespace App\Policies;

use App\Models\Talent;
use App\Models\User;

class TalentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('talents.view');
    }

    public function view(User $user, Talent $talent): bool
    {
        return $user->can('talents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('talents.create');
    }

    public function update(User $user, Talent $talent): bool
    {
        return $user->can('talents.update');
    }

    public function delete(User $user, Talent $talent): bool
    {
        return $user->can('talents.delete');
    }
}
