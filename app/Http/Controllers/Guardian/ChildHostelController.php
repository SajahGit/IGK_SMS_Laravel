<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\HostelAssignment;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChildHostelController extends Controller
{
    public function show(Student $student): View
    {
        $guardian = Auth::user()->guardian()->firstOrFail();
        abort_unless($guardian->students()->whereKey($student->id)->exists(), 404);
        $assignments = HostelAssignment::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
            ->whereHas('room.hostel', function ($query) use ($student) {
                if ($student->branch_id) {
                    $query->where('branch_id', $student->branch_id);
                }
            })
            ->whereHas('academicYear')
            ->with(['room.hostel', 'academicYear'])
            ->orderByDesc('start_date')
            ->get();

        return view('Guardian.ChildHostel', compact('student', 'assignments'));
    }
}
