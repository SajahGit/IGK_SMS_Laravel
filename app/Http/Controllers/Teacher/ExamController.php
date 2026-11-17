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
    public function index()
    {
        $teacher = auth()->user()->teacher;
        $classIds = SchoolHelper::teacherClassIds($teacher);

        $exams = Exam::with(['class', 'subject'])->withCount('results')
            ->where(function ($query) use ($classIds) {
                $query->where('created_by', auth()->id());
                if ($classIds->isNotEmpty()) {
                    $query->orWhereIn('class_id', $classIds);
                }
            })
            ->orderByDesc('exam_date')
            ->latest('id')
            ->paginate(10);

        return view('Teacher.TeacherExamsIndex', compact('exams'));
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

        return view('Teacher.TeacherExamsCreate', compact('assignments'));
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
