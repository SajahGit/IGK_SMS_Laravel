<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\HostelAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HostelController extends Controller
{
    public function index(): View
    {
        $student = Auth::user()->student()->firstOrFail();
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

        return view('Student.Hostel', compact('assignments'));
    }
}
