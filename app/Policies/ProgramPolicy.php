<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('programs.read');
    }

    public function view(User $user, Program $model): bool
    {
        return $user->can('programs.read');
    }

    public function create(User $user): bool
    {
        return $user->can('programs.create');
    }

    public function update(User $user, Program $model): bool
    {
        return $user->can('programs.update');
    }

    public function delete(User $user, Program $model): bool
    {
        return $user->can('programs.delete');
    }
}
