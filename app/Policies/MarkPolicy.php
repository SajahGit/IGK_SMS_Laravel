<?php

namespace App\Policies;

use App\Models\User;

class MarkPolicy
{
    public function enter(User $user, $classSubject): bool
    {
        if (! $user->can('marks.update')) {
            return false;
        }
        if ($user->isTeacher() && $user->teacher) {
            $teacherId = $user->teacher->id ?? $user->teacher->staff_id ?? null;
            if (method_exists($classSubject, 'teacher_id') && $classSubject->teacher_id) {
                return $classSubject->teacher_id == $teacherId || $classSubject->staff_id == $teacherId;
            }
        }
        return $user->can('marks.update');
    }
}
