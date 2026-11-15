<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin() || $user->hasRole('Super Admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('programs.read') || $user->hasAnyRole(['Institution Admin', 'Registrar', 'Super Admin']);
    }

    public function view(User $user, Program $model): bool
    {
        return $user->can('programs.read') || $user->hasAnyRole(['Institution Admin', 'Registrar', 'Super Admin']);
    }

    public function create(User $user): bool
    {
        return $user->can('programs.create') || $user->hasAnyRole(['Institution Admin', 'Super Admin']);
    }

    public function update(User $user, Program $model): bool
    {
        return $user->can('programs.update') || $user->hasAnyRole(['Institution Admin', 'Super Admin']);
    }

    public function delete(User $user, Program $model): bool
    {
        return $user->can('programs.delete') || $user->hasAnyRole(['Institution Admin', 'Super Admin']);
    }
}
