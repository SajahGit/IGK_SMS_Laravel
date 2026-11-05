<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('students.read');
    }

    public function view(User $user, Student $model): bool
    {
        if ($user->isStudent() && $user->student?->id === $model->id) {
            return true;
        }

        return $user->can('students.read');
    }

    public function create(User $user): bool
    {
        return $user->can('students.create');
    }

    public function update(User $user, Student $model): bool
    {
        if ($user->isStudent() && $user->student?->id === $model->id) {
            return true;
        }

        return $user->can('students.update');
    }

    public function delete(User $user, Student $model): bool
    {
        return $user->can('students.delete');
    }
}
