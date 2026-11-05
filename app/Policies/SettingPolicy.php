<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.read');
    }

    public function view(User $user, Setting $model): bool
    {
        return $user->can('settings.read');
    }

    public function create(User $user): bool
    {
        return $user->can('settings.update');
    }

    public function update(User $user, ?Setting $model = null): bool
    {
        return $user->can('settings.update');
    }

    public function delete(User $user, Setting $model): bool
    {
        return $user->can('settings.update');
    }
}
