<?php

namespace App\Policies;

use App\Models\ProgramType;
use App\Models\User;

class ProgramTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('program-types.view');
    }

    public function view(User $user, ProgramType $programType): bool
    {
        return $user->can('program-types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('program-types.create');
    }

    public function update(User $user, ProgramType $programType): bool
    {
        return $user->can('program-types.update');
    }

    public function delete(User $user, ProgramType $programType): bool
    {
        return $user->can('program-types.delete');
    }
}
