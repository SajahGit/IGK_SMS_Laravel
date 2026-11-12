<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LmsAssignment;
use App\Models\LmsCourse;
use App\Models\LmsCourseMaterial;
use App\Models\Student;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LmsController extends Controller
{
    public function index(): View
    {
        $student = $this->student();
        $classSubjectIds = $student->classSubjects()
            ->wherePivot('status', 'enrolled')
            ->pluck('class_subject.id');
        $courses = LmsCourse::query()
            ->whereIn('class_subject_id', $classSubjectIds)
            ->with(['classSubject.subject', 'classSubject.schoolClass'])
            ->orderBy('title')
            ->get();

        return view('Student.LmsIndex', compact('courses'));
    }

    public function show(LmsCourse $course): View
    {
        $student = $this->student();
        $course = $this->enrolledCourse($student, $course->id);
        $course->load([
            'classSubject.subject',
            'classSubject.schoolClass',
            'lessons' => fn ($query) => $query->whereNotNull('published_at'),
            'assignments' => fn ($query) => $query
                ->whereNotNull('published_at')
                ->with(['submissions' => fn ($submissions) => $submissions->where('student_id', $student->id)]),
            'materials' => fn ($query) => $query->whereNotNull('published_at'),
        ]);

        return view('Student.LmsShow', compact('course', 'student'));
    }

    public function downloadMaterial(LmsCourse $course, LmsCourseMaterial $material)
    {
        $student = $this->student();
        $course = $this->enrolledCourse($student, $course->id);
        $material = $course->materials()
            ->whereNotNull('published_at')
            ->findOrFail($material->id);
        abort_unless(Storage::disk('local')->exists($material->file_path), 404, 'PDF file not found.');

        return Storage::disk('local')->download($material->file_path, $material->original_name);
    }

    public function submit(Request $request, LmsCourse $course, LmsAssignment $assignment): RedirectResponse
    {
        $student = $this->student();
        $course = $this->enrolledCourse($student, $course->id);
        $assignment = $course->assignments()
            ->whereNotNull('published_at')
            ->findOrFail($assignment->id);

        if ($assignment->due_at?->isPast()) {
            return back()->withErrors(['submission' => 'The submission deadline has passed.']);
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:50000']]);
        $submission = $assignment->submissions()->where('student_id', $student->id)->first();
        if ($submission?->graded_at) {
            return back()->withErrors(['submission' => 'A graded submission can no longer be changed.']);
        }

        $old = $submission?->only(['body', 'submitted_at']);
        $submission = $assignment->submissions()->updateOrCreate(
            ['student_id' => $student->id],
            ['body' => $data['body'], 'submitted_at' => now()]
        );
        AuditLogger::log(
            $old ? 'lms_submission_updated' : 'lms_submission_created',
            $submission,
            $old,
            $submission->only(['assignment_id', 'student_id', 'body', 'submitted_at'])
        );

        return back()->with('success', 'Assignment submitted.');
    }

    private function student(): Student
    {
        return Student::query()->where('user_id', Auth::id())->firstOrFail();
    }

    private function enrolledCourse(Student $student, int $courseId): LmsCourse
    {
        $classSubjectIds = $student->classSubjects()
            ->wherePivot('status', 'enrolled')
            ->pluck('class_subject.id');

        return LmsCourse::query()
            ->whereIn('class_subject_id', $classSubjectIds)
            ->findOrFail($courseId);
    }
}
