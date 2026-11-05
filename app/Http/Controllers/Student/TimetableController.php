<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TimetableSlot;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function index(): View
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile linked to your account.');

        $slots = TimetableSlot::with(['classSubject.subject', 'classSubject.teacher.user'])
            ->whereHas('classSubject', fn ($q) => $q->where('class_id', $student->class_id))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return view('Student.StudentTimetableIndex', compact('student', 'slots'));
    }
}
