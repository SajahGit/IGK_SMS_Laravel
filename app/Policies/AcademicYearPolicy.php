<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('academic_years.read');
    }

    public function view(User $user, AcademicYear $model): bool
    {
        return $user->can('academic_years.read');
    }

    public function create(User $user): bool
    {
        return $user->can('academic_years.create');
    }

    public function update(User $user, AcademicYear $model): bool
    {
        return $user->can('academic_years.update');
    }

    public function delete(User $user, AcademicYear $model): bool
    {
        return $user->can('academic_years.delete');
    }

    public function setCurrent(User $user, AcademicYear $model): bool
    {
        return $user->can('academic_years.update');
    }
}
