<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Term;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user()->teacher;
        $classIds = SchoolHelper::teacherClassIds($teacher);
        $search = $request->query('search') ?: $request->query('q');
        $classId = $request->query('class_id');
        $subjectId = $request->query('subject_id');

        $teacherClasses = \App\Models\SchoolClass::whereIn('id', $classIds)->orderBy('name')->get();
        $teacherSubjects = \App\Models\Subject::whereHas('classSubjects', fn ($cs) => $cs->where('teacher_id', $teacher?->id))
            ->orderBy('name')
            ->get();

        $exams = Exam::with(['class', 'subject'])->withCount('results')
            ->where(function ($query) use ($classIds) {
                $query->where('created_by', auth()->id());
                if ($classIds->isNotEmpty()) {
                    $query->orWhereIn('class_id', $classIds);
                }
            })
            ->when($search, function ($query, $s) {
                $query->where(function ($w) use ($s) {
                    $w->where('name', 'like', "%{$s}%")
                        ->orWhere('type', 'like', "%{$s}%")
                        ->orWhereHas('subject', fn ($sub) => $sub->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"))
                        ->orWhereHas('class', fn ($cls) => $cls->where('name', 'like', "%{$s}%")->orWhere('section', 'like', "%{$s}%"));
                });
            })
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->orderByDesc('updated_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('Teacher.TeacherExamsIndex', compact(
            'exams',
            'search',
            'classId',
            'subjectId',
            'teacherClasses',
            'teacherSubjects'
        ));
    }

    public function create()
    {
        $teacher = auth()->user()->teacher;
        $currentTermId = Term::where('is_current', true)->value('id');

        $query = ClassSubject::with(['schoolClass', 'subject', 'term'])
            ->where('teacher_id', $teacher?->id)
            ->whereHas('schoolClass')
            ->whereHas('subject');

        if ($currentTermId) {
            $currentTermAssignments = (clone $query)->where(function ($terms) use ($currentTermId) {
                $terms->whereNull('term_id')->orWhere('term_id', $currentTermId);
            })->get();

            $assignments = $currentTermAssignments->isNotEmpty() ? $currentTermAssignments : $query->get();
        } else {
            $assignments = $query->get();
        }

        $assignmentOptions = $assignments
            ->filter(fn (ClassSubject $assignment) => $assignment->schoolClass && $assignment->subject)
            ->map(fn (ClassSubject $assignment) => [
                'id' => $assignment->id,
                'class_id' => $assignment->class_id,
                'class_name' => $assignment->schoolClass->full_name,
                'subject_name' => $assignment->subject->name,
                'term_name' => $assignment->term?->name ?? 'All terms',
            ])
            ->values();
        $selectedAssignmentId = old('assignment', $assignmentOptions->first()['id'] ?? null);
        $selectedClassId = $assignmentOptions->firstWhere('id', $selectedAssignmentId)['class_id']
            ?? $assignmentOptions->first()['class_id']
            ?? null;

        return view('Teacher.TeacherExamsCreate', compact(
            'assignments',
            'assignmentOptions',
            'selectedAssignmentId',
            'selectedClassId'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'assignment' => ['required', 'integer', 'exists:class_subject,id'],
            'exam_date' => 'required|date',
            'max_marks' => 'required|integer|min:1|max:1000',
        ]);

        $teacher = auth()->user()->teacher;
        $assignment = ClassSubject::with('term')
            ->whereKey($data['assignment'])
            ->where('teacher_id', $teacher?->id)
            ->whereHas('teacher', fn ($query) => $query->where('status', 'active'))
            ->firstOrFail();
        SchoolHelper::authorizeTeacherClassSubject($teacher, $assignment->class_id, $assignment->id);

        Exam::create([
            'name' => $data['name'],
            'type' => 'final',
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
            'term_id' => $assignment->term_id,
            'exam_date' => $data['exam_date'],
            'max_marks' => $data['max_marks'],
            'weight' => 100,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('teacher.exams.index')->with('success', 'Exam created. Now enter the marks.');
    }
}
