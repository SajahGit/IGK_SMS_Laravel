<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.AdminDashboard', [
            'studentCount' => Student::count(),
            'teacherCount' => Teacher::count(),
            'classCount' => SchoolClass::count(),
            'subjectCount' => Subject::count(),
            'guardianCount' => Guardian::count(),
            'latestStudents' => Student::with(['user', 'class'])->latest()->take(5)->get(),
        ]);
    }
}
