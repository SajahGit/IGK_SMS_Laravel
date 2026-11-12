<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('users.read');
    }

    public function view(User $user, User $model): bool
    {
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        if (! $this->sameBranch($user, $model)) {
            return false;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('users.read');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        if (! $this->sameBranch($user, $model)) {
            return false;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('users.update');
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        if (! $this->sameBranch($user, $model)) {
            return false;
        }

        return $user->can('users.delete');
    }

    public function restore(User $user): bool
    {
        return $user->can('users.delete');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('users.delete');
    }

    private function sameBranch(User $actor, User $target): bool
    {
        return $actor->isAdmin()
            || ($actor->branch_id !== null && (int) $actor->branch_id === (int) $target->branch_id);
    }
}
