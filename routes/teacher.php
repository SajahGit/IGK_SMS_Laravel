<?php

use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\ClassController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\ExamController;
use App\Http\Controllers\Teacher\LmsController;
use App\Http\Controllers\Teacher\MarkController;
use App\Http\Controllers\Teacher\TimetableController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable.index');

Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
Route::get('/classes/{schoolClass}/students', [ClassController::class, 'students'])->name('classes.students');

Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');

Route::get('/exams/{exam}/marks', [MarkController::class, 'edit'])->name('marks.edit');
Route::post('/exams/{exam}/marks', [MarkController::class, 'update'])->name('marks.update');
Route::get('/exams/{exam}/results', [MarkController::class, 'show'])->name('marks.show');

Route::get('/lms', [LmsController::class, 'index'])->name('lms.index');
Route::post('/lms/courses', [LmsController::class, 'storeCourse'])->name('lms.courses.store');
Route::get('/lms/courses/{course}', [LmsController::class, 'show'])->name('lms.courses.show');
Route::put('/lms/courses/{course}', [LmsController::class, 'updateCourse'])->name('lms.courses.update');
Route::delete('/lms/courses/{course}', [LmsController::class, 'destroyCourse'])->name('lms.courses.destroy');
Route::post('/lms/courses/{course}/materials', [LmsController::class, 'storeMaterial'])->name('lms.materials.store');
Route::get('/lms/courses/{course}/materials/{material}/download', [LmsController::class, 'downloadMaterial'])->name('lms.materials.download');
Route::delete('/lms/courses/{course}/materials/{material}', [LmsController::class, 'destroyMaterial'])->name('lms.materials.destroy');
Route::post('/lms/courses/{course}/lessons', [LmsController::class, 'storeLesson'])->name('lms.lessons.store');
Route::put('/lms/courses/{course}/lessons/{lesson}', [LmsController::class, 'updateLesson'])->name('lms.lessons.update');
Route::delete('/lms/courses/{course}/lessons/{lesson}', [LmsController::class, 'destroyLesson'])->name('lms.lessons.destroy');
Route::post('/lms/courses/{course}/assignments', [LmsController::class, 'storeAssignment'])->name('lms.assignments.store');
Route::put('/lms/courses/{course}/assignments/{assignment}', [LmsController::class, 'updateAssignment'])->name('lms.assignments.update');
Route::delete('/lms/courses/{course}/assignments/{assignment}', [LmsController::class, 'destroyAssignment'])->name('lms.assignments.destroy');
Route::put('/lms/courses/{course}/assignments/{assignment}/submissions/{submission}', [LmsController::class, 'gradeSubmission'])
    ->name('lms.submissions.grade');
