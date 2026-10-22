<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Student;
use App\Support\SchoolHelper;

class ChildResultController extends Controller
{
    public function show(Student $student)
    {
        $this->authorizeChild($student);

        $results = Result::with('exam.subject')->where('student_id', $student->id)->get()
            ->sortByDesc(fn ($r) => $r->exam->exam_date);

        return view('guardian.GuardianResults', [
            'student' => $student->load(['user', 'class']),
            'children' => auth()->user()->guardian->students()->with('user')->get(),
            'results' => $results,
            'average' => SchoolHelper::studentAverage($student->id),
        ]);
    }

    private function authorizeChild(Student $student): void
    {
        $guardian = auth()->user()->guardian;
        abort_unless($guardian && $student->guardian_id === $guardian->id, 403, 'This student is not linked to your account.');
    }
}
