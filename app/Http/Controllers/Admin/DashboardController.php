<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Support\BranchContext;

class DashboardController extends Controller
{
    public function index()
    {
        $branchId = BranchContext::activeBranchId();

        return view('Admin.AdminDashboard', [
            'studentCount' => Student::count(),
            'teacherCount' => Teacher::query()
                ->when($branchId !== null, fn ($query) => $query->whereHas(
                    'user',
                    fn ($userQuery) => $userQuery->where('branch_id', $branchId)
                ))
                ->count(),
            'classCount' => SchoolClass::count(),
            'subjectCount' => Subject::count(),
            'guardianCount' => Guardian::inActiveBranch()->count(),
            'latestStudents' => Student::with(['user', 'class'])->latest()->take(5)->get(),
        ]);
    }
}
