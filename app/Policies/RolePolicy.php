<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles_and_permissions.read');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles_and_permissions.read');
    }

    public function create(User $user): bool
    {
        return $user->can('roles_and_permissions.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles_and_permissions.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles_and_permissions.delete');
    }
}
