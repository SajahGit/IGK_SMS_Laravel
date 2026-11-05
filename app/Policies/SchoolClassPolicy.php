<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;

class SchoolClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('classes.read');
    }

    public function view(User $user, SchoolClass $model): bool
    {
        return $user->can('classes.read');
    }

    public function create(User $user): bool
    {
        return $user->can('classes.create');
    }

    public function update(User $user, SchoolClass $model): bool
    {
        return $user->can('classes.update');
    }

    public function delete(User $user, SchoolClass $model): bool
    {
        return $user->can('classes.delete');
    }
}
