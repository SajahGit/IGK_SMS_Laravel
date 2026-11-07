<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\UpdateMarksRequest;
use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;
use App\Services\MarksEntryService;
use App\Services\ResultPublicationService;
use App\Support\SchoolHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MarkController extends Controller
{
    public function edit(Exam $exam): View
    {
        $this->authorizeExam($exam);
        ResultPublicationService::verifyNotLocked($exam);

        $students = Student::with('user')
            ->whereIn('id', MarksEntryService::enrolledStudentIds($exam))
            ->orderBy('admission_no')->get();
        $existing = $exam->results()->get()->keyBy('student_id');

        return view('Teacher.TeacherMarksEdit', compact('exam', 'students', 'existing'));
    }

    public function update(UpdateMarksRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $marksInput = $request->input('marks', []);
        $absentInput = $request->input('is_absent', []);
        $excusedInput = $request->input('is_excused', []);

        MarksEntryService::bulkSave($exam, $marksInput, $absentInput, $excusedInput);

        return redirect()->route('teacher.marks.show', $exam)->with('success', 'Marks saved successfully.');
    }

    public function show(Exam $exam): View
    {
        $this->authorizeExam($exam);

        return view('Teacher.TeacherMarksShow', self::summary($exam));
    }

    /** Results + stats for one exam (shared with the admin read-only view). */
    public static function summary(Exam $exam): array
    {
        $exam->load(['class', 'subject', 'term']);
        $results = Result::with('student.user')->where('exam_id', $exam->id)->orderByDesc('marks')->get();

        $validMarks = $results->where('is_absent', false)->whereNotNull('marks');

        $stats = [
            'count' => $results->count(),
            'absent' => $results->where('is_absent', true)->count(),
            'average' => $validMarks->count() ? round($validMarks->avg('marks'), 1) : 0,
            'highest' => $validMarks->max('marks'),
            'lowest' => $validMarks->min('marks'),
            'passed' => $validMarks->filter(fn ($r) => SchoolHelper::resultPercentage($r->marks, $exam->max_marks) >= 40)->count(),
        ];

        return compact('exam', 'results', 'stats');
    }

    private function authorizeExam(Exam $exam): void
    {
        SchoolHelper::authorizeTeacherClass(auth()->user()->teacher, $exam->class_id);
    }
}
