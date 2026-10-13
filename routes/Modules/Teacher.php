<?php

use App\Http\Controllers\Teacher\AttendanceRecordController;
use App\Http\Controllers\Teacher\AttendanceSessionController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\ExamController;
use App\Http\Controllers\Teacher\ExamSubjectController;
use App\Http\Controllers\Teacher\MarkEntryController;
use App\Http\Controllers\Teacher\MyClassController;
use App\Http\Controllers\Teacher\PublishResultController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('my-classes', [MyClassController::class, 'index'])->name('my-classes.index');
    Route::get('my-classes/{classSubject}', [MyClassController::class, 'show'])->name('my-classes.show');

    Route::resource('attendance', AttendanceSessionController::class)
        ->parameters(['attendance' => 'session'])
        ->only(['index', 'create', 'store', 'show']);
    Route::put('attendance/{session}/records', [AttendanceRecordController::class, 'update'])->name('attendance.records.update');

    Route::resource('exams', ExamController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('exams/{exam}/subjects', [ExamSubjectController::class, 'store'])->name('exams.subjects.store');
    Route::post('exams/{exam}/publish', PublishResultController::class)->name('exams.publish');
    Route::delete('exam-subjects/{examSubject}', [ExamSubjectController::class, 'destroy'])->name('exam-subjects.destroy');

    Route::get('exam-subjects/{examSubject}/marks', [MarkEntryController::class, 'show'])->name('marks.show');
    Route::post('exam-subjects/{examSubject}/marks', [MarkEntryController::class, 'store'])->name('marks.store');
});
