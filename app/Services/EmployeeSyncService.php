<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class EmployeeSyncService
{
    public const CATEGORIES = [
        'teaching' => 'Teaching Faculty',
        'administration' => 'Administration',
        'finance' => 'Finance & Accounts',
        'registrar' => 'Admissions & Registrar',
        'library' => 'Library & Operations',
        'other' => 'General Staff',
    ];

    public static function ensureForTeacher(Teacher $teacher): Employee
    {
        $existing = Employee::withoutGlobalScopes()
            ->where(function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
                if ($teacher->user_id) {
                    $q->orWhere('user_id', $teacher->user_id);
                }
            })
            ->first();

        if ($existing) {
            if (! $existing->teacher_id) {
                $existing->forceFill(['teacher_id' => $teacher->id])->save();
            }

            return $existing;
        }

        $branchId = $teacher->branch_id
            ?? $teacher->user?->branch_id
            ?? Branch::withoutGlobalScopes()->where('is_active', true)->orderByDesc('is_main')->value('id');

        return Employee::create([
            'employee_no' => self::nextEmployeeNo(),
            'user_id' => $teacher->user_id,
            'teacher_id' => $teacher->id,
            'department_id' => $teacher->department_id,
            'branch_id' => $branchId,
            'job_title' => 'Teacher',
            'hire_date' => $teacher->created_at?->toDateString() ?? today()->toDateString(),
            'employment_status' => match ($teacher->status) {
                'on_leave' => 'on_leave',
                'inactive' => 'terminated',
                default => 'active',
            },
            'base_salary' => '0.00',
            'apit_mode' => 'primary',
        ]);
    }

    public static function ensureForStaffUser(User $user): ?Employee
    {
        $role = strtolower((string) $user->role);
        if (in_array($role, ['student', 'parent', 'guardian', ''], true)) {
            return null;
        }

        $teacher = Teacher::withoutGlobalScopes()->where('user_id', $user->id)->first();
        if ($teacher) {
            return self::ensureForTeacher($teacher);
        }

        $existing = Employee::withoutGlobalScopes()->where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        $branchId = $user->branch_id
            ?? Branch::withoutGlobalScopes()->where('is_active', true)->orderByDesc('is_main')->value('id');

        $jobTitle = match ($role) {
            'super-admin', 'super admin' => 'Super Administrator',
            'admin', 'institution admin' => 'Institution Admin',
            'accountant' => 'Accountant',
            'registrar' => 'Registrar',
            'librarian' => 'Librarian',
            'teacher' => 'Teacher',
            default => ucwords(str_replace(['-', '_'], ' ', $role)),
        };

        return Employee::create([
            'employee_no' => self::nextEmployeeNo(),
            'user_id' => $user->id,
            'teacher_id' => null,
            'department_id' => null,
            'branch_id' => $branchId,
            'job_title' => $jobTitle,
            'hire_date' => $user->created_at?->toDateString() ?? today()->toDateString(),
            'employment_status' => $user->is_active ? 'active' : 'terminated',
            'base_salary' => '0.00',
            'apit_mode' => 'primary',
        ]);
    }

    public static function syncAllMissing(): int
    {
        $created = 0;

        foreach (Teacher::withoutGlobalScopes()->with('user')->get() as $teacher) {
            $before = Employee::withoutGlobalScopes()
                ->where('teacher_id', $teacher->id)
                ->orWhere('user_id', $teacher->user_id)
                ->exists();
            self::ensureForTeacher($teacher);
            if (! $before) {
                $created++;
            }
        }

        $staffUsers = User::query()
            ->whereNotIn('role', ['student', 'parent', 'guardian'])
            ->get();

        foreach ($staffUsers as $user) {
            $before = Employee::withoutGlobalScopes()->where('user_id', $user->id)->exists();
            $emp = self::ensureForStaffUser($user);
            if (! $before && $emp) {
                $created++;
            }
        }

        return $created;
    }

    public static function categoryFor(Employee $employee): string
    {
        $role = strtolower((string) ($employee->user?->role ?? $employee->teacher?->user?->role ?? ''));
        $title = strtolower((string) $employee->job_title);

        if ($employee->teacher_id !== null || $role === 'teacher' || str_contains($title, 'teacher') || str_contains($title, 'lecturer') || str_contains($title, 'professor')) {
            return 'teaching';
        }

        if (in_array($role, ['super-admin', 'super admin', 'admin', 'institution admin'], true)
            || str_contains($title, 'admin')
            || str_contains($title, 'principal')
            || str_contains($title, 'director')) {
            return 'administration';
        }

        if ($role === 'accountant' || str_contains($title, 'account') || str_contains($title, 'finance') || str_contains($title, 'bursar')) {
            return 'finance';
        }

        if ($role === 'registrar' || str_contains($title, 'registrar') || str_contains($title, 'admission')) {
            return 'registrar';
        }

        if ($role === 'librarian' || str_contains($title, 'librar')) {
            return 'library';
        }

        return 'other';
    }

    public static function applyCategoryFilter(Builder $query, string $category): Builder
    {
        return match ($category) {
            'teaching' => $query->where(function (Builder $q) {
                $q->whereNotNull('teacher_id')
                    ->orWhere('job_title', 'like', '%Teacher%')
                    ->orWhere('job_title', 'like', '%Lecturer%')
                    ->orWhere('job_title', 'like', '%Professor%')
                    ->orWhereHas('user', fn ($u) => $u->where('role', 'teacher'));
            }),
            'administration' => $query->whereNull('teacher_id')->where(function (Builder $q) {
                $q->where('job_title', 'like', '%Admin%')
                    ->orWhere('job_title', 'like', '%Principal%')
                    ->orWhere('job_title', 'like', '%Director%')
                    ->orWhereHas('user', fn ($u) => $u->whereIn('role', ['super-admin', 'super admin', 'admin', 'institution admin']));
            }),
            'finance' => $query->whereNull('teacher_id')->where(function (Builder $q) {
                $q->where('job_title', 'like', '%Account%')
                    ->orWhere('job_title', 'like', '%Finance%')
                    ->orWhere('job_title', 'like', '%Bursar%')
                    ->orWhereHas('user', fn ($u) => $u->where('role', 'accountant'));
            }),
            'registrar' => $query->whereNull('teacher_id')->where(function (Builder $q) {
                $q->where('job_title', 'like', '%Registrar%')
                    ->orWhere('job_title', 'like', '%Admission%')
                    ->orWhereHas('user', fn ($u) => $u->where('role', 'registrar'));
            }),
            'library' => $query->whereNull('teacher_id')->where(function (Builder $q) {
                $q->where('job_title', 'like', '%Librar%')
                    ->orWhereHas('user', fn ($u) => $u->where('role', 'librarian'));
            }),
            'other' => $query->whereNull('teacher_id')
                ->where('job_title', 'not like', '%Teacher%')
                ->where('job_title', 'not like', '%Lecturer%')
                ->where('job_title', 'not like', '%Professor%')
                ->where('job_title', 'not like', '%Admin%')
                ->where('job_title', 'not like', '%Principal%')
                ->where('job_title', 'not like', '%Director%')
                ->where('job_title', 'not like', '%Account%')
                ->where('job_title', 'not like', '%Finance%')
                ->where('job_title', 'not like', '%Bursar%')
                ->where('job_title', 'not like', '%Registrar%')
                ->where('job_title', 'not like', '%Admission%')
                ->where('job_title', 'not like', '%Librar%')
                ->whereDoesntHave('user', fn ($u) => $u->whereIn('role', ['teacher', 'super-admin', 'super admin', 'admin', 'institution admin', 'accountant', 'registrar', 'librarian'])),
            default => $query,
        };
    }

    private static function nextEmployeeNo(): string
    {
        $nextNumber = (int) (Employee::withoutGlobalScopes()->max('id') ?? 0) + 1;

        do {
            $candidate = 'EMP-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Employee::withoutGlobalScopes()->where('employee_no', $candidate)->exists());

        return $candidate;
    }
}
