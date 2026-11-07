<?php

namespace App\Http\Requests\Teacher;

use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Services\AttendanceSessionService;
use App\Support\SchoolHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->isAdmin()
            || auth()->user()->isTeacher()
            || auth()->user()->can('attendance.create')
            || auth()->user()->can('attendance.update')
            || strtolower(auth()->user()->role) === 'registrar'
        );
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'exists:classes,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'start_time' => ['required_without:attendance_session_id', 'nullable', 'date_format:H:i'],
            'class_subject_id' => ['nullable', 'integer', 'exists:class_subject,id'],
            'attendance_session_id' => ['nullable', 'integer', 'exists:attendance_sessions,id'],
            'status' => ['required', 'array'],
            'status.*' => [Rule::in(['present', 'absent', 'late', 'excused'])],
            'remarks' => ['nullable', 'array'],
            'remarks.*' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function passedValidation(): void
    {
        $classId = (int) $this->input('class_id');
        $date = (string) $this->input('date');
        $classSubjectId = $this->input('class_subject_id')
            ? (int) $this->input('class_subject_id')
            : null;

        if ($sessionId = $this->input('attendance_session_id')) {
            $session = AttendanceSession::findOrFail($sessionId);
            abort_unless(
                (int) $session->class_id === $classId
                    && $session->date->toDateString() === $date
                    && (! $this->filled('class_subject_id') || (int) $session->class_subject_id === (int) $this->input('class_subject_id')),
                422,
                'The selected attendance session does not match the class, subject, and date.'
            );
            $classSubjectId = $session->class_subject_id;
        }

        if (auth()->user()->isTeacher()) {
            SchoolHelper::authorizeTeacherClass(auth()->user()->teacher, $classId);
            if ($classSubjectId) {
                SchoolHelper::authorizeTeacherClassSubject(auth()->user()->teacher, $classId, $classSubjectId);
            }
        }

        if (auth()->user()->isTeacher()) {
            abort_unless($classSubjectId, 403, 'Attendance must be recorded for a subject you teach.');
        }

        if (! auth()->user()->isAdmin() && ! auth()->user()->isTeacher() && ! auth()->user()->can('attendance.create')) {
            abort_unless($this->input('attendance_session_id'), 403, 'You may update attendance only for an existing session.');
        }

        if ($classSubjectId) {
            abort_unless(
                ClassSubject::whereKey($classSubjectId)->where('class_id', $classId)->exists(),
                422,
                'The selected subject is not assigned to this class.'
            );
        }

        AttendanceSessionService::verifyEditWindow(
            $classId,
            $date,
            $classSubjectId ? (int) $classSubjectId : null,
            $this->input('start_time'),
            $this->input('attendance_session_id') ? (int) $this->input('attendance_session_id') : null
        );

        $classStudentIds = AttendanceSessionService::enrolledStudentIds($classId, $classSubjectId)
            ->map(fn ($id) => (string) $id);
        $submittedStudentIds = collect(array_keys($this->input('status', [])))->map(fn ($id) => (string) $id);
        if (
            $submittedStudentIds->diff($classStudentIds)->isNotEmpty()
            || $classStudentIds->diff($submittedStudentIds)->isNotEmpty()
        ) {
            abort(422, 'Attendance must include every active student enrolled in the selected class-subject.');
        }

    }
}
