<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Services\ElectiveRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ElectiveController extends Controller
{
    public function index(ElectiveRegistrationService $service): View
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile linked to your account.');

        $term = $service->currentTermFor($student);
        $offerings = $service->offeringsFor($student);
        $offeringIds = $offerings->modelKeys();
        $enrollmentStatuses = $offeringIds === []
            ? collect()
            : DB::table('student_subject_enrollments')
                ->where('student_id', $student->id)
                ->whereIn('class_subject_id', $offeringIds)
                ->pluck('status', 'class_subject_id');
        $enrollmentCounts = $offeringIds === []
            ? collect()
            : DB::table('student_subject_enrollments')
                ->selectRaw('class_subject_id, COUNT(*) AS total')
                ->whereIn('class_subject_id', $offeringIds)
                ->where('status', 'enrolled')
                ->groupBy('class_subject_id')
                ->pluck('total', 'class_subject_id');

        return view('Student.StudentElectivesIndex', [
            'student' => $student,
            'term' => $term,
            'offerings' => $offerings,
            'enrollmentStatuses' => $enrollmentStatuses,
            'enrollmentCounts' => $enrollmentCounts,
            'registrationOpen' => $service->registrationIsOpen($student),
        ]);
    }

    public function register(ClassSubject $offering, ElectiveRegistrationService $service): RedirectResponse
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile linked to your account.');

        $service->register($student, $offering);

        return back()->with('success', 'Elective registered successfully.');
    }

    public function drop(ClassSubject $offering, ElectiveRegistrationService $service): RedirectResponse
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile linked to your account.');

        $service->drop($student, $offering);

        return back()->with('success', 'Elective dropped successfully.');
    }
}
