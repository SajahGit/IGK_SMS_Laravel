<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TimetableSlot;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function index(): View
    {
        $teacher = auth()->user()->teacher;
        abort_unless($teacher, 403, 'No teacher profile linked to your account.');

        $slots = TimetableSlot::with(['classSubject.schoolClass', 'classSubject.subject'])
            ->whereHas('classSubject', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->whereHas('classSubject.schoolClass')
            ->whereHas('classSubject.subject')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return view('Teacher.TeacherTimetableIndex', compact('slots'));
    }
}
