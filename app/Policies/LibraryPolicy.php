<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LibraryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('library.read');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->can('library.create');
    }

    public function update(User $user, Model $record): bool
    {
        return $user->isAdmin() || $user->can('library.update');
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->isAdmin() || $user->can('library.delete');
    }
}
