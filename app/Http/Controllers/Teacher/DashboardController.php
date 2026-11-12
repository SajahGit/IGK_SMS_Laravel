<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Student;
use App\Support\SchoolHelper;

class DashboardController extends Controller
{
    public function index()
    {
        $teacher = auth()->user()->teacher;
        $classIds = SchoolHelper::teacherClassIds($teacher);

        return view('Teacher.TeacherDashboard', [
            'assignments' => $teacher
                ? ClassSubject::with(['schoolClass', 'subject'])
                    ->where('teacher_id', $teacher->id)
                    ->whereHas('schoolClass')
                    ->whereHas('subject')
                    ->get()
                : collect(),
            'classCount' => $classIds->count(),
            'studentCount' => Student::whereIn('class_id', $classIds)->count(),
            'upcomingExams' => Exam::with(['class', 'subject'])
                ->where('created_by', auth()->id())
                ->whereDate('exam_date', '>=', today())
                ->orderBy('exam_date')->take(5)->get(),
        ]);
    }
}
