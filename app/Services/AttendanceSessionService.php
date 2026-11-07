<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceSessionService
{
    public static function enrolledStudentIds(int $classId, ?int $classSubjectId): Collection
    {
        $query = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active');

        if ($classSubjectId) {
            $enrolledStudentIds = DB::table('student_subject_enrollments')
                ->where('class_subject_id', $classSubjectId)
                ->where('status', 'enrolled')
                ->pluck('student_id');
            $query->whereIn('id', $enrolledStudentIds);
        }

        return $query->orderBy('admission_no')->pluck('id');
    }

    public static function create(int $classId, string $date, string $startTime, ?int $classSubjectId, int $userId): AttendanceSession
    {
        return DB::transaction(function () use ($classId, $date, $startTime, $classSubjectId, $userId) {
            DB::table('classes')->where('id', $classId)->lockForUpdate()->first();

            $query = AttendanceSession::where('class_id', $classId)
                ->whereDate('date', $date)
                ->where('start_time', 'like', $startTime.'%');
            $classSubject = null;
            if ($classSubjectId) {
                $classSubject = ClassSubject::where('id', $classSubjectId)
                    ->where('class_id', $classId)->firstOrFail();
                $query->where('class_subject_id', $classSubjectId);
            } else {
                $query->whereNull('class_subject_id');
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'start_time' => 'An attendance session already exists for this class, subject, date, and start time.',
                ]);
            }

            return AttendanceSession::create([
                'class_id' => $classId,
                'class_subject_id' => $classSubject?->id,
                'term_id' => $classSubject?->term_id ?? Term::where('is_current', true)->value('id'),
                'date' => $date,
                'start_time' => $startTime,
                'session_label' => $classSubject?->subject?->name ?? 'General',
                'taken_by' => $userId,
            ]);
        });
    }

    public static function verifyEditWindow(
        int $classId,
        string $date,
        ?int $classSubjectId = null,
        ?string $startTime = null,
        ?int $sessionId = null
    ): void {
        $query = AttendanceSession::where('class_id', $classId)->whereDate('date', $date);
        if ($sessionId) {
            $query->whereKey($sessionId);
        } else {
            $query->when($classSubjectId, fn ($sessions) => $sessions->where('class_subject_id', $classSubjectId));
            if (! $classSubjectId) {
                $query->whereNull('class_subject_id');
            }
            if ($startTime) {
                $query->whereTime('start_time', $startTime);
            }
        }

        $session = $query->first();
        $existingRecord = Attendance::where('class_id', $classId)
            ->whereDate('date', $date)
            ->when(
                $session,
                fn ($records) => $records->where('attendance_session_id', $session->id),
                fn ($records) => $records->whereNull('attendance_session_id')
            )
            ->oldest()
            ->first();

        if (! $session && ! $existingRecord) {
            return;
        }

        $sessionLocked = $session?->locked_at !== null;
        $sessionExpired = $session && $session->created_at->diffInHours(now()) > 48;
        $legacyExpired = ! $session && $existingRecord && $existingRecord->created_at->diffInHours(now()) > 48;

        if ($sessionLocked || (($sessionExpired || $legacyExpired) && ! auth()->user()->isAdmin() && strtolower(auth()->user()->role) !== 'registrar')) {
            abort(403, 'Attendance edit window (48h) has expired. Contact Admin.');
        }
    }
}
