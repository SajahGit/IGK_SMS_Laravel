<?php

use App\Http\Controllers\Report\ReportPdfController;
use App\Http\Controllers\Report\StudentFinancialLedgerReportController;
use App\Http\Controllers\Student\AttendanceController;
use App\Http\Controllers\Student\CafeteriaController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\ElectiveController;
use App\Http\Controllers\Student\HostelController;
use App\Http\Controllers\Student\InvoiceController;
use App\Http\Controllers\Student\LmsController;
use App\Http\Controllers\Student\PayHereController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\ResultController;
use App\Http\Controllers\Student\TimetableController;
use App\Http\Controllers\TranscriptController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::get('/documents/{document}/download', [ProfileController::class, 'downloadDocument'])->name('documents.download');

Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable');
Route::get('/electives', [ElectiveController::class, 'index'])->name('electives.index');
Route::post('/electives/{offering}/register', [ElectiveController::class, 'register'])->name('electives.register');
Route::post('/electives/{offering}/drop', [ElectiveController::class, 'drop'])->name('electives.drop');
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
Route::get('/results', [ResultController::class, 'index'])->name('results');
Route::get('/report-card', [ResultController::class, 'reportCard'])->name('report-card');
Route::get('/report-card/pdf', [ResultController::class, 'downloadReportCard'])->name('report-card.pdf');
Route::get('/transcript', [TranscriptController::class, 'student'])->name('transcript');
Route::get('/transcript/pdf', [TranscriptController::class, 'studentPdf'])->name('transcript.pdf');
Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
Route::get('/invoices/{invoice}/payhere', [PayHereController::class, 'create'])->name('payhere.create');
Route::post('/invoices/{invoice}/payhere', [PayHereController::class, 'store'])->middleware('throttle:10,1')->name('payhere.store');
Route::get('/payhere/{transaction}/return', [PayHereController::class, 'returned'])->name('payhere.return');
Route::get('/payhere/{transaction}/cancel', [PayHereController::class, 'cancelled'])->name('payhere.cancel');
Route::get('/ledger', [StudentFinancialLedgerReportController::class, 'index'])->name('ledger');
Route::get('/ledger/pdf', [ReportPdfController::class, 'downloadStudentLedger'])->middleware('throttle:exports')->name('ledger.pdf');
Route::get('/ledger/csv', [StudentFinancialLedgerReportController::class, 'csv'])->middleware('throttle:exports')->name('ledger.csv');
Route::get('/hostel', [HostelController::class, 'index'])->name('hostel');
Route::get('/cafeteria', [CafeteriaController::class, 'index'])->name('cafeteria');
Route::post('/cafeteria/orders', [CafeteriaController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('cafeteria.orders.store');
Route::post('/cafeteria/orders/{order}/cancel', [CafeteriaController::class, 'cancel'])
    ->middleware('throttle:30,1')
    ->name('cafeteria.orders.cancel');
Route::get('/lms', [LmsController::class, 'index'])->name('lms.index');
Route::get('/lms/courses/{course}', [LmsController::class, 'show'])->name('lms.courses.show');
Route::get('/lms/courses/{course}/materials/{material}/download', [LmsController::class, 'downloadMaterial'])->name('lms.materials.download');
Route::post('/lms/courses/{course}/assignments/{assignment}/submission', [LmsController::class, 'submit'])
    ->name('lms.submissions.store');
