<?php

return [
    'roles' => ['admin', 'teacher', 'student', 'parent'],

    'attendance_statuses' => ['present', 'absent', 'late', 'excused'],

    'exam_types' => ['midterm', 'final', 'quiz', 'assignment'],

    'student_statuses' => ['active', 'suspended', 'graduated', 'withdrawn'],

    // Highest "min" first. Used by App\Services\GradingService
    'grade_scale' => [
        ['min' => 75, 'grade' => 'A', 'point' => 4.0],
        ['min' => 65, 'grade' => 'B', 'point' => 3.0],
        ['min' => 55, 'grade' => 'C', 'point' => 2.0],
        ['min' => 40, 'grade' => 'S', 'point' => 1.0],
        ['min' => 0,  'grade' => 'F', 'point' => 0.0],
    ],

    'admission_prefix' => 'ADM',
    'default_password' => 'password',
    'low_attendance_threshold' => 75,
];
