<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;
use App\Support\SchoolHelper;
use Illuminate\Http\Request;

class MarkController extends Controller
{
    public function edit(Exam $exam)
    {
        $this->authorizeExam($exam);

        $students = Student::with('user')->where('class_id', $exam->class_id)->orderBy('admission_no')->get();
        $existing = $exam->results()->pluck('marks', 'student_id');

        return view('teacher.TeacherMarksEdit', compact('exam', 'students', 'existing'));
    }

    public function update(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        $request->validate([
            'marks' => 'required|array',
            'marks.*' => 'nullable|numeric|min:0|max:'.$exam->max_marks,
        ], [
            'marks.*.max' => 'Marks cannot be more than '.$exam->max_marks.'.',
            'marks.*.min' => 'Marks cannot be negative.',
            'marks.*.numeric' => 'Marks must be a number.',
        ]);

        $studentIds = Student::where('class_id', $exam->class_id)->pluck('id');

        foreach ($request->input('marks') as $studentId => $mark) {
            if (! $studentIds->contains((int) $studentId)) {
                continue;
            }

            if ($mark === null || $mark === '') {
                Result::where('exam_id', $exam->id)->where('student_id', $studentId)->delete();
                continue;
            }

            Result::updateOrCreate(
                ['exam_id' => $exam->id, 'student_id' => $studentId],
                ['marks' => $mark]
            );
        }

        return redirect()->route('teacher.marks.show', $exam)->with('success', 'Marks saved.');
    }

    public function show(Exam $exam)
    {
        $this->authorizeExam($exam);

        $exam->load(['class', 'subject']);
        $results = Result::with('student.user')->where('exam_id', $exam->id)->orderByDesc('marks')->get();

        $stats = [
            'count' => $results->count(),
            'average' => $results->count() ? round($results->avg('marks'), 1) : 0,
            'highest' => $results->max('marks'),
            'lowest' => $results->min('marks'),
            'passed' => $results->filter(fn ($r) => SchoolHelper::resultPercentage($r->marks, $exam->max_marks) >= 40)->count(),
        ];

        return view('teacher.TeacherMarksShow', compact('exam', 'results', 'stats'));
    }

    private function authorizeExam(Exam $exam): void
    {
        SchoolHelper::authorizeTeacherClass(auth()->user()->teacher, $exam->class_id);
    }
}
