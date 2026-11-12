<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\LmsAssignment;
use App\Models\LmsCourse;
use App\Models\LmsCourseMaterial;
use App\Models\LmsLesson;
use App\Models\LmsSubmission;
use App\Models\Teacher;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LmsController extends Controller
{
    public function index(): View
    {
        $teacher = $this->teacher();
        $courses = LmsCourse::query()
            ->whereHas('classSubject', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->whereHas('classSubject.schoolClass')
            ->whereHas('classSubject.subject')
            ->with(['classSubject.subject', 'classSubject.schoolClass', 'assignments.submissions'])
            ->withCount('lessons')
            ->orderBy('title')
            ->get();
        $offerings = ClassSubject::query()
            ->where('teacher_id', $teacher->id)
            ->whereDoesntHave('learningCourse')
            ->whereHas('schoolClass')
            ->whereHas('subject')
            ->with(['subject', 'schoolClass', 'term'])
            ->orderBy('id')
            ->get();

        return view('Teacher.LmsIndex', compact('courses', 'offerings'));
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_subject_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $teacher = $this->teacher();
        $offering = ClassSubject::query()
            ->whereKey($data['class_subject_id'])
            ->where('teacher_id', $teacher->id)
            ->whereDoesntHave('learningCourse')
            ->firstOrFail();

        $course = LmsCourse::create([
            'class_subject_id' => $offering->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);
        AuditLogger::log('lms_course_created', $course, null, $course->only(['class_subject_id', 'title', 'description']));

        return redirect()->route('teacher.lms.courses.show', $course)->with('success', 'Course created.');
    }

    public function show(LmsCourse $course): View
    {
        $course = $this->ownedCourse($course->id);
        $course->load([
            'classSubject.subject',
            'classSubject.schoolClass',
            'lessons',
            'assignments.submissions.student.user',
            'materials',
        ]);

        return view('Teacher.LmsShow', compact('course'));
    }

    public function updateCourse(Request $request, LmsCourse $course): RedirectResponse
    {
        $course = $this->ownedCourse($course->id);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $old = $course->only(['title', 'description']);
        $course->update($data);
        AuditLogger::log('lms_course_updated', $course, $old, $data);

        return back()->with('success', 'Course updated.');
    }

    public function destroyCourse(LmsCourse $course): RedirectResponse
    {
        $course = $this->ownedCourse($course->id);
        if ($course->assignments()->whereHas('submissions')->exists()) {
            return back()->withErrors(['course' => 'This course has student submissions and cannot be deleted.']);
        }

        $disk = Storage::disk('local');
        foreach ($course->materials as $material) {
            if ($disk->exists($material->file_path) && ! $disk->delete($material->file_path)) {
                abort(500, 'A course PDF could not be removed from private storage.');
            }
        }

        AuditLogger::log('lms_course_deleted', $course, $course->getAttributes(), null);
        $course->delete();

        return redirect()->route('teacher.lms.index')->with('success', 'Course deleted.');
    }

    public function storeLesson(Request $request, LmsCourse $course): RedirectResponse
    {
        $course = $this->ownedCourse($course->id);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:50000'],
            'position' => ['required', 'integer', 'min:1', 'max:65535', Rule::unique('lms_lessons')->where('course_id', $course->id)],
            'published' => ['nullable', 'boolean'],
        ]);
        $lesson = $course->lessons()->create([
            'title' => $data['title'],
            'content' => $data['content'],
            'position' => $data['position'],
            'published_at' => $request->boolean('published') ? now() : null,
        ]);
        AuditLogger::log('lms_lesson_created', $lesson, null, $lesson->only(['course_id', 'title', 'position', 'published_at']));

        return back()->with('success', 'Lesson created.');
    }

    public function updateLesson(Request $request, LmsCourse $course, LmsLesson $lesson): RedirectResponse
    {
        $course = $this->ownedCourse($course->id);
        $lesson = $course->lessons()->findOrFail($lesson->id);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:50000'],
            'position' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('lms_lessons')->where('course_id', $course->id)->ignore($lesson->id),
            ],
            'published' => ['nullable', 'boolean'],
        ]);
        $old = $lesson->only(['title', 'content', 'position', 'published_at']);
        $lesson->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'position' => $data['position'],
            'published_at' => $request->boolean('published') ? ($lesson->published_at ?? now()) : null,
        ]);
        AuditLogger::log('lms_lesson_updated', $lesson, $old, $lesson->only(['title', 'content', 'position', 'published_at']));

        return back()->with('success', 'Lesson updated.');
    }

    public function destroyLesson(LmsCourse $course, LmsLesson $lesson): RedirectResponse
    {
        $course = $this->ownedCourse($course->id);
        $lesson = $course->lessons()->findOrFail($lesson->id);
        AuditLogger::log('lms_lesson_deleted', $lesson, $lesson->getAttributes(), null);
        $lesson->delete();

        return back()->with('success', 'Lesson deleted.');
    }

    public function storeMaterial(Request $request, LmsCourse $course): RedirectResponse
    {
        $course = $this->ownedCourse($course->id);
        $data = $request->validate([
            'type' => ['required', Rule::in(['module', 'past_paper'])],
            'title' => [
                'required',
                'string',
                'max:160',
                Rule::unique('lms_course_materials', 'title')
                    ->where('course_id', $course->id)
                    ->where('type', $request->input('type')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'published' => ['nullable', 'boolean'],
        ]);
        $file = $request->file('file');
        $path = $file->store('lms/courses/'.$course->id, 'local');
        abort_unless(is_string($path) && $path !== '', 500, 'The PDF could not be saved to private storage.');
        $material = $course->materials()->create([
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => Auth::id(),
            'published_at' => $request->boolean('published') ? now() : null,
        ]);
        AuditLogger::log('lms_material_created', $material, null, $material->only([
            'course_id', 'type', 'title', 'original_name', 'published_at',
        ]));

        return back()->with('success', 'PDF material uploaded.');
    }

    public function downloadMaterial(LmsCourse $course, LmsCourseMaterial $material)
    {
        $course = $this->ownedCourse($course->id);
        $material = $course->materials()->findOrFail($material->id);
        abort_unless(Storage::disk('local')->exists($material->file_path), 404, 'PDF file not found.');

        return Storage::disk('local')->download($material->file_path, $material->original_name);
    }

    public function destroyMaterial(LmsCourse $course, LmsCourseMaterial $material): RedirectResponse
    {
        $course = $this->ownedCourse($course->id);
        $material = $course->materials()->findOrFail($material->id);
        $disk = Storage::disk('local');
        if ($disk->exists($material->file_path) && ! $disk->delete($material->file_path)) {
            abort(500, 'The PDF could not be removed from private storage.');
        }

        AuditLogger::log('lms_material_deleted', $material, $material->only([
            'course_id', 'type', 'title', 'original_name',
        ]), null);
        $material->delete();

        return back()->with('success', 'PDF material deleted.');
    }

    public function storeAssignment(Request $request, LmsCourse $course): RedirectResponse
    {
        $course = $this->ownedCourse($course->id);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'instructions' => ['required', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
            'max_points' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'published' => ['nullable', 'boolean'],
        ]);
        $assignment = $course->assignments()->create([
            'title' => $data['title'],
            'instructions' => $data['instructions'],
            'due_at' => $data['due_at'] ?? null,
            'max_points' => $data['max_points'],
            'published_at' => $request->boolean('published') ? now() : null,
        ]);
        AuditLogger::log('lms_assignment_created', $assignment, null, $assignment->only(['course_id', 'title', 'due_at', 'max_points', 'published_at']));

        return back()->with('success', 'Assignment created.');
    }

    public function updateAssignment(Request $request, LmsCourse $course, LmsAssignment $assignment): RedirectResponse
    {
        $course = $this->ownedCourse($course->id);
        $assignment = $course->assignments()->findOrFail($assignment->id);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'instructions' => ['required', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
            'max_points' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'published' => ['nullable', 'boolean'],
        ]);

        if ($assignment->submissions()->exists()
            && number_format((float) $data['max_points'], 2, '.', '') !== $assignment->max_points) {
            return back()->withErrors(['max_points' => 'Maximum points cannot change after students submit this assignment.']);
        }

        $old = $assignment->only(['title', 'instructions', 'due_at', 'max_points', 'published_at']);
        $assignment->update([
            'title' => $data['title'],
            'instructions' => $data['instructions'],
            'due_at' => $data['due_at'] ?? null,
            'max_points' => $assignment->submissions()->exists() ? $assignment->max_points : $data['max_points'],
            'published_at' => $request->boolean('published') ? ($assignment->published_at ?? now()) : null,
        ]);
        AuditLogger::log('lms_assignment_updated', $assignment, $old, $assignment->only(['title', 'instructions', 'due_at', 'max_points', 'published_at']));

        return back()->with('success', 'Assignment updated.');
    }

    public function destroyAssignment(LmsCourse $course, LmsAssignment $assignment): RedirectResponse
    {
        $course = $this->ownedCourse($course->id);
        $assignment = $course->assignments()->findOrFail($assignment->id);
        if ($assignment->submissions()->exists()) {
            return back()->withErrors(['assignment' => 'This assignment has student submissions and cannot be deleted.']);
        }

        AuditLogger::log('lms_assignment_deleted', $assignment, $assignment->getAttributes(), null);
        $assignment->delete();

        return back()->with('success', 'Assignment deleted.');
    }

    public function gradeSubmission(
        Request $request,
        LmsCourse $course,
        LmsAssignment $assignment,
        LmsSubmission $submission
    ): RedirectResponse {
        $course = $this->ownedCourse($course->id);
        $assignment = $course->assignments()->findOrFail($assignment->id);
        $submission = $assignment->submissions()->with('student')->findOrFail($submission->id);
        abort_if($submission->student->user_id === Auth::id(), 403);

        $data = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0', 'max:'.$assignment->max_points],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);
        $old = $submission->only(['score', 'feedback', 'graded_at', 'graded_by']);
        $submission->update([
            'score' => $data['score'] ?? null,
            'feedback' => $data['feedback'] ?? null,
            'graded_at' => isset($data['score']) ? now() : null,
            'graded_by' => isset($data['score']) ? Auth::id() : null,
        ]);
        AuditLogger::log('lms_submission_graded', $submission, $old, $submission->only(['score', 'feedback', 'graded_at', 'graded_by']));

        return back()->with('success', 'Submission grade saved.');
    }

    private function teacher(): Teacher
    {
        return Teacher::query()->where('user_id', Auth::id())->firstOrFail();
    }

    private function ownedCourse(int $courseId): LmsCourse
    {
        $teacherId = $this->teacher()->id;

        return LmsCourse::query()
            ->whereHas('classSubject', fn ($query) => $query->where('teacher_id', $teacherId))
            ->findOrFail($courseId);
    }
}
