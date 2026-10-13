<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::with('creator')
            ->withCount('examSubjects')
            ->orderByDesc('id')
            ->paginate(20);

        return view('Teacher.Exams.Index', ['exams' => $exams]);
    }

    public function create()
    {
        return view('Teacher.Exams.Create', ['term' => Term::current()]);
    }

    public function store(Request $request)
    {
        $term = Term::current();
        if (! $term) {
            return back()->with('error', 'No current term. Ask the admin to create one.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100',
                Rule::unique('exams', 'name')->where('term_id', $term->id)],
            'type' => ['required', Rule::in(config('school.exam_types'))],
        ], ['name.unique' => 'An exam with this name already exists in this term.']);

        $exam = Exam::create($data + [
            'term_id' => $term->id,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('teacher.exams.show', $exam)->with('success', 'Exam created. Now add your subject below.');
    }

    public function show(Exam $exam)
    {
        $exam->load(['term.academicYear', 'creator', 'examSubjects.classSubject.section.classGroup',
            'examSubjects.classSubject.subject', 'examSubjects.classSubject.staff.user']);
        $exam->examSubjects->loadCount('marks');

        $staffId = auth()->user()->staff?->id;
        $attached = $exam->examSubjects->pluck('class_subject_id');

        // my subjects that are not yet part of this exam
        $available = ClassSubject::with(['section.classGroup', 'subject'])
            ->where('staff_id', $staffId)
            ->where('term_id', $exam->term_id)
            ->whereNotIn('id', $attached)
            ->get();

        return view('Teacher.Exams.Show', [
            'exam' => $exam,
            'available' => $available,
            'myIds' => ClassSubject::where('staff_id', $staffId)->pluck('id')->all(),
            'canPublish' => $exam->created_by === auth()->id() && ! $exam->isPublished(),
        ]);
    }

    public function destroy(Exam $exam)
    {
        abort_unless($exam->created_by === auth()->id(), 403, 'Only the creator can delete this exam.');

        if ($exam->isPublished()) {
            return back()->with('error', 'A published exam cannot be deleted.');
        }

        $exam->delete();

        return redirect()->route('teacher.exams.index')->with('success', 'Exam deleted.');
    }
}
