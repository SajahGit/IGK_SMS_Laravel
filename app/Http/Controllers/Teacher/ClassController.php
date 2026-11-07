<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\SchoolHelper;

class ClassController extends Controller
{
    public function index()
    {
        $teacher = auth()->user()->teacher;

        $assignments = $teacher
            ? ClassSubject::with(['schoolClass', 'subject'])->where('teacher_id', $teacher->id)->get()
            : collect();

        return view('Teacher.TeacherClassesIndex', compact('assignments'));
    }

    public function students(SchoolClass $schoolClass)
    {
        SchoolHelper::authorizeTeacherClass(auth()->user()->teacher, $schoolClass->id);

        $students = Student::with(['user', 'guardian.user'])
            ->where('class_id', $schoolClass->id)
            ->orderBy('admission_no')
            ->get();

        return view('Teacher.TeacherClassesStudents', compact('schoolClass', 'students'));
    }
}
