<?php

namespace App\Policies;

use App\Models\Curriculum;
use App\Models\User;

class CurriculumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('curriculum.read');
    }

    public function view(User $user, Curriculum $model): bool
    {
        return $user->can('curriculum.read');
    }

    public function create(User $user): bool
    {
        return $user->can('curriculum.create');
    }

    public function update(User $user, Curriculum $model): bool
    {
        return $user->can('curriculum.update');
    }

    public function delete(User $user, Curriculum $model): bool
    {
        return $user->can('curriculum.delete');
    }
}
