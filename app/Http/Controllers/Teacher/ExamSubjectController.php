<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSubject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamSubjectController extends Controller
{
    public function store(Request $request, Exam $exam)
    {
        abort_if($exam->isPublished(), 403, 'This exam is already published.');

        $data = $request->validate([
            'class_subject_id' => ['required', 'exists:class_subjects,id',
                Rule::unique('exam_subjects', 'class_subject_id')->where('exam_id', $exam->id)],
            'max_marks' => ['required', 'integer', 'min:1', 'max:1000'],
            'pass_marks' => ['required', 'integer', 'min:0', 'lte:max_marks'],
            'exam_date' => ['nullable', 'date'],
        ], ['class_subject_id.unique' => 'This subject is already part of the exam.']);

        abort_unless($request->user()->teachesClassSubject((int) $data['class_subject_id']), 403, 'This is not your class.');

        $exam->examSubjects()->create($data);

        return back()->with('success', 'Subject added to the exam.');
    }

    public function destroy(ExamSubject $examSubject)
    {
        abort_unless(auth()->user()->teachesClassSubject($examSubject->class_subject_id), 403, 'This is not your class.');
        abort_if($examSubject->exam->isPublished(), 403, 'This exam is already published.');

        $examSubject->delete(); // its marks are removed too

        return back()->with('success', 'Subject removed from the exam.');
    }
}
