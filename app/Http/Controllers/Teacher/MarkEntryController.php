<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ExamSubject;
use App\Models\Mark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarkEntryController extends Controller
{
    public function show(ExamSubject $examSubject)
    {
        $this->authorizeTeacher($examSubject);

        $examSubject->load(['exam', 'classSubject.section.classGroup', 'classSubject.subject']);

        $students = $examSubject->classSubject->section->students()
            ->where('status', 'active')->orderBy('first_name')->get();

        return view('Teacher.Marks.Show', [
            'examSubject' => $examSubject,
            'students' => $students,
            'marks' => $examSubject->marks()->get()->keyBy('student_id'),
            'locked' => $examSubject->exam->isPublished(),
        ]);
    }

    public function store(Request $request, ExamSubject $examSubject)
    {
        $this->authorizeTeacher($examSubject);
        abort_if($examSubject->exam->isPublished(), 403, 'Results are published. Marks are locked.');

        $max = $examSubject->max_marks;
        $students = $examSubject->classSubject->section->students()->where('status', 'active')->get();

        // pass 1: validate everything
        $errors = [];
        foreach ($students as $s) {
            $absent = $request->boolean("absent.{$s->id}");
            $raw = $request->input("marks.{$s->id}");

            if ($absent || $raw === null || $raw === '') {
                continue;
            }
            if (! is_numeric($raw) || $raw < 0 || $raw > $max) {
                $errors["marks.{$s->id}"] = "{$s->full_name} ({$s->admission_no}): marks must be a number from 0 to {$max}.";
            }
        }
        if ($errors) {
            return back()->withErrors($errors)->withInput();
        }

        // pass 2: save
        DB::transaction(function () use ($request, $students, $examSubject) {
            foreach ($students as $s) {
                $absent = $request->boolean("absent.{$s->id}");
                $raw = $request->input("marks.{$s->id}");

                if (! $absent && ($raw === null || $raw === '')) {
                    Mark::where('exam_subject_id', $examSubject->id)->where('student_id', $s->id)->delete();
                    continue;
                }

                Mark::updateOrCreate(
                    ['exam_subject_id' => $examSubject->id, 'student_id' => $s->id],
                    [
                        'marks_obtained' => $absent ? null : $raw,
                        'is_absent' => $absent,
                        'entered_by' => $request->user()->id,
                    ]
                );
            }
        });

        return back()->with('success', 'Marks saved.');
    }

    private function authorizeTeacher(ExamSubject $examSubject): void
    {
        abort_unless(
            auth()->user()->teachesClassSubject($examSubject->class_subject_id),
            403,
            'This is not your class.'
        );
    }
}
