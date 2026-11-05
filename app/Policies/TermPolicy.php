<?php

namespace App\Policies;

use App\Models\Term;
use App\Models\User;

class TermPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('terms.read');
    }

    public function view(User $user, Term $model): bool
    {
        return $user->can('terms.read');
    }

    public function create(User $user): bool
    {
        return $user->can('terms.create');
    }

    public function update(User $user, Term $model): bool
    {
        return $user->can('terms.update');
    }

    public function delete(User $user, Term $model): bool
    {
        return $user->can('terms.delete');
    }

    public function setCurrent(User $user, Term $model): bool
    {
        return $user->can('terms.update');
    }
}
