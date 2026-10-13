<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;

abstract class StudentBaseController extends Controller
{
    /** A student can only ever see his OWN record (never an id from the URL). */
    protected function me(): Student
    {
        $student = auth()->user()->student;

        abort_unless($student, 404, 'No student profile is linked to your account. Please contact the admin.');

        return $student->load('section.classGroup');
    }
}
