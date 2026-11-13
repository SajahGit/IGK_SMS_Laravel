<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\GradeScale;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\GradingService;
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
        $excused = (int) ($counts['excused'] ?? 0);
        $total = $present + $absent + $late + $excused;
        $eligible = $total - $excused;

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'percentage' => $eligible > 0 ? round((($present + $late) / $eligible) * 100, 1) : 0,
        ];
    }

    public static function attendanceSummary(
        int $studentId,
        ?string $from = null,
        ?string $to = null,
        ?int $termId = null,
        ?int $subjectId = null
    ): array {
        $query = Attendance::where('student_id', $studentId);

        if ($from) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('date', '<=', $to);
        }
        if ($termId) {
            $query->whereHas('session', fn ($session) => $session->where('term_id', $termId));
        }
        if ($subjectId) {
            $query->whereHas('session.classSubject', fn ($subject) => $subject->where('subject_id', $subjectId));
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
        return GradingService::percentage($marks, (int) $maxMarks);
    }

    /** A 75+, B 65-74, C 55-64, S 40-54, F below 40 */
    public static function gradeFor($percentage, ?GradeScale $scale = null): string
    {
        return GradingService::grade((float) $percentage, $scale);
    }

    /** Average percentage over all exam results of a student (null when no results). */
    public static function studentAverage(int $studentId): ?float
    {
        $results = Result::with('exam')->where('student_id', $studentId)->get();

        if ($results->isEmpty()) {
            return null;
        }

        $gradedResults = $results->reject(fn ($result) => $result->is_excused);

        return $gradedResults->isNotEmpty()
            ? round($gradedResults->avg(fn ($r) => self::resultPercentage($r->marks, $r->exam->max_marks)), 1)
            : null;
    }

    /** Class ids the teacher teaches (from class_subject). */
    public static function teacherClassIds(?Teacher $teacher): Collection
    {
        if (! $teacher) {
            return collect();
        }

        $currentTermId = Term::where('is_current', true)->value('id');

        return ClassSubject::where('teacher_id', $teacher->id)
            ->whereHas('teacher', fn ($query) => $query->where('status', 'active'))
            ->whereHas('schoolClass')
            ->where(function ($query) use ($currentTermId) {
                $query->whereNull('term_id');
                if ($currentTermId) {
                    $query->orWhere('term_id', $currentTermId);
                }
            })
            ->pluck('class_id')->unique()->values();
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

    public static function authorizeTeacherClassSubject(?Teacher $teacher, int $classId, int $classSubjectId): void
    {
        $currentTermId = Term::where('is_current', true)->value('id');
        abort_unless(
            $teacher && $teacher->status === 'active' && ClassSubject::whereKey($classSubjectId)
                ->where('class_id', $classId)
                ->where('teacher_id', $teacher->id)
                ->where(function ($query) use ($currentTermId) {
                    $query->whereNull('term_id');
                    if ($currentTermId) {
                        $query->orWhere('term_id', $currentTermId);
                    }
                })
                ->exists(),
            403,
            'You do not teach this class subject.'
        );
    }

    /** Classes a user may use in reports: authorized administrators = all, teachers = own classes. */
    public static function allowedClasses(User $user): Collection
    {
        if ($user->isAdmin() || $user->can('reports.academic') || $user->can('reports.financial')) {
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
        if (in_array($grade, ['A*', 'A', '9', '8', '7', 'Exceeding'], true)) {
            return 'success';
        }

        if (in_array($grade, ['B', '6', '5', 'Secure'], true)) {
            return 'primary';
        }

        if (in_array($grade, ['C', '4', '3', 'Developing'], true)) {
            return 'info';
        }

        if (in_array($grade, ['D', 'E', 'F', 'G', '1', '2', 'Emerging'], true)) {
            return 'warning';
        }

        return 'danger';
    }

    /** Grade points mapping: A (75-100) = 4.0, B (65-74) = 3.0, C (55-64) = 2.0, S (40-54) = 1.0, F (0-39) = 0.0 */
    public static function gradePointFor(float|int|null $percentage, ?GradeScale $scale = null): float
    {
        return GradingService::gradePoint($percentage ?? 0.0, $scale);
    }

    /**
     * Calculate Term GPA from a collection of results with exam.subject.
     * sum(grade_point * subject.credit_hours) / sum(subject.credit_hours) rounded to 2 decimal places.
     */
    public static function calculateTermGpa(Collection $results, ?GradeScale $scale = null): ?float
    {
        return GradingService::calculateTermGpa($results, $scale);
    }
}
