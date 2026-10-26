<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::with(['class', 'subject'])->withCount('results')
            ->where('created_by', auth()->id())
            ->orderByDesc('exam_date')
            ->paginate(10);

        return view('teacher.TeacherExamsIndex', compact('exams'));
    }

    public function create()
    {
        $teacher = auth()->user()->teacher;

        $assignments = $teacher
            ? ClassSubject::with(['schoolClass', 'subject'])->where('teacher_id', $teacher->id)->get()
            : collect();

        return view('teacher.TeacherExamsCreate', compact('assignments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'assignment' => ['required', 'regex:/^\d+-\d+$/'], // "classId-subjectId"
            'exam_date' => 'required|date',
            'max_marks' => 'required|integer|min:1|max:1000',
        ]);

        [$classId, $subjectId] = array_map('intval', explode('-', $data['assignment']));
        $teacher = auth()->user()->teacher;

        // The teacher may only create exams for a class and subject they teach.
        abort_unless(
            $teacher && ClassSubject::where('teacher_id', $teacher->id)
                ->where('class_id', $classId)->where('subject_id', $subjectId)->exists(),
            403,
            'You do not teach this subject in that class.'
        );

        Exam::create([
            'name' => $data['name'],
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'exam_date' => $data['exam_date'],
            'max_marks' => $data['max_marks'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('teacher.exams.index')->with('success', 'Exam created. Now enter the marks.');
    }
}
