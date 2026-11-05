<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('subjects.read');
    }

    public function view(User $user, Subject $model): bool
    {
        return $user->can('subjects.read');
    }

    public function create(User $user): bool
    {
        return $user->can('subjects.create');
    }

    public function update(User $user, Subject $model): bool
    {
        return $user->can('subjects.update');
    }

    public function delete(User $user, Subject $model): bool
    {
        return $user->can('subjects.delete');
    }
}
