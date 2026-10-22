<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Shared calculations. Every module must use these so numbers and grades match everywhere.
 */
class SchoolHelper
{
    /** Build the summary array from counts keyed by status. */
    public static function summaryFromCounts($counts): array
    {
        $present = (int) ($counts['present'] ?? 0);
        $absent = (int) ($counts['absent'] ?? 0);
        $late = (int) ($counts['late'] ?? 0);
        $total = $present + $absent + $late;

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            // late counts as attended
            'percentage' => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0,
        ];
    }

    public static function attendanceSummary(int $studentId, ?string $from = null, ?string $to = null): array
    {
        $query = Attendance::where('student_id', $studentId);

        if ($from) {
            $query->where('date', '>=', $from);
        }
        if ($to) {
            $query->where('date', '<=', $to);
        }

        $counts = $query->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return self::summaryFromCounts($counts);
    }

    public static function attendancePercentage(int $studentId, ?string $from = null, ?string $to = null): float
    {
        return (float) self::attendanceSummary($studentId, $from, $to)['percentage'];
    }

    public static function resultPercentage($marks, $maxMarks): float
    {
        return $maxMarks > 0 ? round(($marks / $maxMarks) * 100, 1) : 0;
    }

    /** A 75+, B 65-74, C 55-64, S 40-54, F below 40 */
    public static function gradeFor($percentage): string
    {
        return match (true) {
            $percentage >= 75 => 'A',
            $percentage >= 65 => 'B',
            $percentage >= 55 => 'C',
            $percentage >= 40 => 'S',
            default => 'F',
        };
    }

    /** Average percentage over all exam results of a student (null when no results). */
    public static function studentAverage(int $studentId): ?float
    {
        $results = Result::with('exam')->where('student_id', $studentId)->get();

        if ($results->isEmpty()) {
            return null;
        }

        return round($results->avg(fn ($r) => self::resultPercentage($r->marks, $r->exam->max_marks)), 1);
    }

    /** Class ids the teacher teaches (from class_subject). */
    public static function teacherClassIds(?Teacher $teacher): Collection
    {
        if (! $teacher) {
            return collect();
        }

        return ClassSubject::where('teacher_id', $teacher->id)->pluck('class_id')->unique()->values();
    }

    /** 403 unless the teacher teaches this class. */
    public static function authorizeTeacherClass(?Teacher $teacher, $classId): void
    {
        abort_unless(
            $teacher && self::teacherClassIds($teacher)->contains((int) $classId),
            403,
            'You do not teach this class.'
        );
    }

    /** Classes a user may use in reports: admin = all, teacher = own classes. */
    public static function allowedClasses(User $user): Collection
    {
        if ($user->isAdmin()) {
            return SchoolClass::orderBy('name')->orderBy('section')->get();
        }

        return SchoolClass::whereIn('id', self::teacherClassIds($user->teacher))
            ->orderBy('name')->orderBy('section')->get();
    }

    /** 45.00 -> 45, 45.50 -> 45.5 */
    public static function formatMarks($marks): string
    {
        return rtrim(rtrim(number_format((float) $marks, 2, '.', ''), '0'), '.');
    }

    public static function gradeBadge(string $grade): string
    {
        return match ($grade) {
            'A' => 'success',
            'B' => 'primary',
            'C' => 'info',
            'S' => 'warning',
            default => 'danger',
        };
    }
}
