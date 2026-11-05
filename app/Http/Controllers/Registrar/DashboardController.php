<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('Registrar.RegistrarDashboard', [
            'studentCount' => Student::count(),
            'classCount' => SchoolClass::count(),
            'academicYear' => AcademicYear::where('is_current', true)->first(),
            'term' => Term::where('is_current', true)->first(),
            'pendingExams' => Exam::where('is_published', false)->count(),
            'latestStudents' => Student::with(['user', 'class'])->latest()->take(5)->get(),
        ]);
    }
}
