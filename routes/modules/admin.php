<?php

use App\Http\Controllers\Accountant\FeeStructureController;
use App\Http\Controllers\Accountant\InvoiceController;
use App\Http\Controllers\Accountant\PaymentController;
use App\Http\Controllers\InstitutionAdmin\AcademicYearController;
use App\Http\Controllers\InstitutionAdmin\AnnouncementController;
use App\Http\Controllers\InstitutionAdmin\AuditLogController;
use App\Http\Controllers\InstitutionAdmin\BulkImportController;
use App\Http\Controllers\InstitutionAdmin\ClassSubjectController;
use App\Http\Controllers\InstitutionAdmin\CurriculumController;
use App\Http\Controllers\InstitutionAdmin\CafeteriaController;
use App\Http\Controllers\InstitutionAdmin\DashboardController;
use App\Http\Controllers\InstitutionAdmin\DepartmentController;
use App\Http\Controllers\InstitutionAdmin\ExamController;
use App\Http\Controllers\InstitutionAdmin\GradeScaleController;
use App\Http\Controllers\InstitutionAdmin\GuardianController;
use App\Http\Controllers\InstitutionAdmin\HrPayrollController;
use App\Http\Controllers\InstitutionAdmin\HostelController;
use App\Http\Controllers\InstitutionAdmin\InventoryController;
use App\Http\Controllers\InstitutionAdmin\ProgramController;
use App\Http\Controllers\InstitutionAdmin\SavedFilterController;
use App\Http\Controllers\InstitutionAdmin\SchoolClassController;
use App\Http\Controllers\InstitutionAdmin\SettingController;
use App\Http\Controllers\InstitutionAdmin\StaffAttendanceController;
use App\Http\Controllers\InstitutionAdmin\StudentController;
use App\Http\Controllers\InstitutionAdmin\SubjectController;
use App\Http\Controllers\InstitutionAdmin\TeacherController;
use App\Http\Controllers\InstitutionAdmin\TermController;
use App\Http\Controllers\InstitutionAdmin\TermResultController;
use App\Http\Controllers\InstitutionAdmin\TimetableController;
use App\Http\Controllers\InstitutionAdmin\TransportController;
use App\Http\Controllers\InstitutionAdmin\UserController;
use App\Http\Controllers\SuperAdmin\RoleController;
use App\Http\Controllers\Teacher\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::post('roles/{role}/assign-permissions', [RoleController::class, 'assignPermissions'])->name('roles.assign-permissions');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    Route::resource('settings', SettingController::class)->middleware('permission:settings.read');

    Route::get('teachers/create', [TeacherController::class, 'create'])->middleware('permission:staff.create')->name('teachers.create');
    Route::post('teachers', [TeacherController::class, 'store'])->middleware('permission:staff.create')->name('teachers.store');
    Route::get('teachers/{teacher}/edit', [TeacherController::class, 'edit'])->middleware('permission:staff.update')->name('teachers.edit');
    Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->middleware('permission:staff.update')->name('teachers.update');
    Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])->middleware('permission:staff.delete')->name('teachers.destroy');

});

Route::middleware('permission:imports.create')->group(function () {
    Route::get('imports', [BulkImportController::class, 'index'])->name('imports.index');
    Route::get('imports/template/{entity}', [BulkImportController::class, 'template'])->name('imports.template');
    Route::post('imports', [BulkImportController::class, 'store'])->name('imports.store');
});

Route::middleware('role:admin')->group(function () {
    Route::get('inventory', [InventoryController::class, 'index'])->middleware('permission:inventory.read')->name('inventory.index');
    Route::get('inventory/create', [InventoryController::class, 'create'])->middleware('permission:inventory.create')->name('inventory.create');
    Route::post('inventory', [InventoryController::class, 'store'])->middleware('permission:inventory.create')->name('inventory.store');
    Route::get('inventory/{inventoryItem}', [InventoryController::class, 'show'])->middleware('permission:inventory.read')->name('inventory.show');
    Route::get('inventory/{inventoryItem}/edit', [InventoryController::class, 'edit'])->middleware('permission:inventory.update')->name('inventory.edit');
    Route::put('inventory/{inventoryItem}', [InventoryController::class, 'update'])->middleware('permission:inventory.update')->name('inventory.update');
    Route::delete('inventory/{inventoryItem}', [InventoryController::class, 'destroy'])->middleware('permission:inventory.delete')->name('inventory.destroy');
    Route::post('inventory/{inventoryItem}/movements', [InventoryController::class, 'movement'])->middleware('permission:inventory_movements.create')->name('inventory.movements.store');
});

Route::middleware('role:admin')->prefix('transport')->name('transport.')->group(function () {
    Route::get('/', [TransportController::class, 'index'])->middleware('permission:transport.read')->name('index');
    Route::get('routes/create', [TransportController::class, 'routeCreate'])->middleware('permission:transport.create')->name('routes.create');
    Route::post('routes', [TransportController::class, 'routeStore'])->middleware('permission:transport.create')->name('routes.store');
    Route::get('routes/{transportRoute}/edit', [TransportController::class, 'routeEdit'])->middleware('permission:transport.read')->name('routes.edit');
    Route::put('routes/{transportRoute}', [TransportController::class, 'routeUpdate'])->middleware('permission:transport.update')->name('routes.update');
    Route::delete('routes/{transportRoute}', [TransportController::class, 'routeDestroy'])->middleware('permission:transport.delete')->name('routes.destroy');
    Route::post('routes/{transportRoute}/stops', [TransportController::class, 'stopStore'])->middleware('permission:transport.update')->name('stops.store');
    Route::put('routes/{transportRoute}/stops/{transportStop}', [TransportController::class, 'stopUpdate'])->middleware('permission:transport.update')->name('stops.update');
    Route::delete('routes/{transportRoute}/stops/{transportStop}', [TransportController::class, 'stopDestroy'])->middleware('permission:transport.update')->name('stops.destroy');
    Route::get('vehicles/create', [TransportController::class, 'vehicleCreate'])->middleware('permission:transport.create')->name('vehicles.create');
    Route::post('vehicles', [TransportController::class, 'vehicleStore'])->middleware('permission:transport.create')->name('vehicles.store');
    Route::get('vehicles/{vehicle}/edit', [TransportController::class, 'vehicleEdit'])->middleware('permission:transport.read')->name('vehicles.edit');
    Route::put('vehicles/{vehicle}', [TransportController::class, 'vehicleUpdate'])->middleware('permission:transport.update')->name('vehicles.update');
    Route::delete('vehicles/{vehicle}', [TransportController::class, 'vehicleDestroy'])->middleware('permission:transport.delete')->name('vehicles.destroy');
    Route::get('assignments/create', [TransportController::class, 'assignmentCreate'])->middleware('permission:transport.assign')->name('assignments.create');
    Route::post('assignments', [TransportController::class, 'assignmentStore'])->middleware('permission:transport.assign')->name('assignments.store');
    Route::get('assignments/{assignment}/edit', [TransportController::class, 'assignmentEdit'])->middleware('permission:transport.assign')->name('assignments.edit');
    Route::put('assignments/{assignment}', [TransportController::class, 'assignmentUpdate'])->middleware('permission:transport.assign')->name('assignments.update');
    Route::post('assignments/{assignment}/end', [TransportController::class, 'assignmentEnd'])->middleware('permission:transport.assign')->name('assignments.end');
});

Route::middleware('role:admin')->prefix('hostels')->name('hostels.')->group(function () {
    Route::get('/', [HostelController::class, 'index'])->name('index');
    Route::post('/', [HostelController::class, 'storeHostel'])->name('store');
    Route::put('/{hostel}', [HostelController::class, 'updateHostel'])->name('update');
    Route::delete('/{hostel}', [HostelController::class, 'destroyHostel'])->name('destroy');
    Route::post('/{hostel}/rooms', [HostelController::class, 'storeRoom'])->name('rooms.store');
    Route::put('/rooms/{room}', [HostelController::class, 'updateRoom'])->name('rooms.update');
    Route::delete('/rooms/{room}', [HostelController::class, 'destroyRoom'])->name('rooms.destroy');
    Route::get('/assignments/create', [HostelController::class, 'createAssignment'])->name('assignments.create');
    Route::get('/assignments/{assignment}/edit', [HostelController::class, 'editAssignment'])->name('assignments.edit');
    Route::post('/assignments', [HostelController::class, 'storeAssignment'])->name('assignments.store');
    Route::put('/assignments/{assignment}', [HostelController::class, 'updateAssignment'])->name('assignments.update');
    Route::post('/assignments/{assignment}/end', [HostelController::class, 'endAssignment'])->name('assignments.end');
});

Route::middleware('role:admin')->prefix('cafeteria')->name('cafeteria.')->group(function () {
    Route::get('/', [CafeteriaController::class, 'index'])->name('index');
    Route::post('/items', [CafeteriaController::class, 'storeItem'])->name('items.store');
    Route::put('/items/{item}', [CafeteriaController::class, 'updateItem'])->name('items.update');
    Route::delete('/items/{item}', [CafeteriaController::class, 'destroyItem'])->name('items.destroy');
    Route::post('/orders/{order}/fulfill', [CafeteriaController::class, 'fulfill'])->name('orders.fulfill');
    Route::post('/orders/{order}/cancel', [CafeteriaController::class, 'cancel'])->name('orders.cancel');
});

Route::middleware(['role:admin', 'permission:staff_attendance.read'])->group(function () {
    Route::get('staff-attendance', [StaffAttendanceController::class, 'index'])->name('staff-attendance.index');
});
Route::middleware(['role:admin', 'permission:staff_attendance.create'])->group(function () {
    Route::post('staff-attendance', [StaffAttendanceController::class, 'store'])->name('staff-attendance.store');
});

Route::middleware('role:admin')->prefix('hr')->name('hr.')->group(function () {
    Route::get('employees', [HrPayrollController::class, 'employees'])->middleware('permission:hr.read')->name('employees.index');
    Route::get('employees/create', [HrPayrollController::class, 'employeeCreate'])->middleware('permission:hr.create')->name('employees.create');
    Route::post('employees', [HrPayrollController::class, 'employeeStore'])->middleware('permission:hr.create')->name('employees.store');
    Route::get('employees/{employee}/edit', [HrPayrollController::class, 'employeeEdit'])->middleware('permission:hr.update')->name('employees.edit');
    Route::put('employees/{employee}', [HrPayrollController::class, 'employeeUpdate'])->middleware('permission:hr.update')->name('employees.update');
    Route::delete('employees/{employee}', [HrPayrollController::class, 'employeeDestroy'])->middleware('permission:hr.delete')->name('employees.destroy');
    Route::get('leaves', [HrPayrollController::class, 'leaves'])->middleware('permission:hr.read')->name('leaves.index');
    Route::post('leaves', [HrPayrollController::class, 'leaveStore'])->middleware('permission:hr.create')->name('leaves.store');
    Route::post('leaves/{leaveRequest}/review', [HrPayrollController::class, 'leaveReview'])->middleware('permission:hr.approve')->name('leaves.review');
    Route::get('payroll', [HrPayrollController::class, 'payrollIndex'])->middleware('permission:payroll.read')->name('payroll.index');
    Route::post('payroll', [HrPayrollController::class, 'payrollGenerate'])->middleware('permission:payroll.create')->name('payroll.generate');
    Route::get('payroll/{payrollRun}', [HrPayrollController::class, 'payrollShow'])->middleware('permission:payroll.read')->name('payroll.show');
    Route::put('payroll/entries/{payrollEntry}', [HrPayrollController::class, 'payrollEntryUpdate'])->middleware('permission:payroll.update')->name('payroll.entries.update');
    Route::post('payroll/{payrollRun}/approve', [HrPayrollController::class, 'payrollApprove'])->middleware('permission:payroll.approve')->name('payroll.approve');
    Route::post('payroll/{payrollRun}/paid', [HrPayrollController::class, 'payrollPaid'])->middleware('permission:payroll.pay')->name('payroll.paid');
});

Route::get('users', [UserController::class, 'index'])->middleware('permission:users.read')->name('users.index');
Route::get('users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

Route::get('academic-years', [AcademicYearController::class, 'index'])->middleware('permission:academic_years.read')->name('academic-years.index');
Route::get('academic-years/create', [AcademicYearController::class, 'create'])->middleware('permission:academic_years.create')->name('academic-years.create');
Route::post('academic-years', [AcademicYearController::class, 'store'])->middleware('permission:academic_years.create')->name('academic-years.store');
Route::get('academic-years/{academicYear}/edit', [AcademicYearController::class, 'edit'])->middleware('permission:academic_years.update')->name('academic-years.edit');
Route::put('academic-years/{academicYear}', [AcademicYearController::class, 'update'])->middleware('permission:academic_years.update')->name('academic-years.update');
Route::delete('academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])->middleware('permission:academic_years.delete')->name('academic-years.destroy');
Route::post('academic-years/{academicYear}/set-current', [AcademicYearController::class, 'setCurrent'])->middleware('permission:academic_years.update')->name('academic-years.set-current');

Route::get('terms', [TermController::class, 'index'])->middleware('permission:terms.read')->name('terms.index');
Route::get('terms/create', [TermController::class, 'create'])->middleware('permission:terms.create')->name('terms.create');
Route::post('terms', [TermController::class, 'store'])->middleware('permission:terms.create')->name('terms.store');
Route::get('terms/{term}/edit', [TermController::class, 'edit'])->middleware('permission:terms.update')->name('terms.edit');
Route::put('terms/{term}', [TermController::class, 'update'])->middleware('permission:terms.update')->name('terms.update');
Route::delete('terms/{term}', [TermController::class, 'destroy'])->middleware('permission:terms.delete')->name('terms.destroy');
Route::post('terms/{term}/set-current', [TermController::class, 'setCurrent'])->middleware('permission:terms.update')->name('terms.set-current');

Route::get('departments', [DepartmentController::class, 'index'])->middleware('permission:departments.read')->name('departments.index');
Route::get('departments/create', [DepartmentController::class, 'create'])->middleware('permission:departments.create')->name('departments.create');
Route::post('departments', [DepartmentController::class, 'store'])->middleware('permission:departments.create')->name('departments.store');
Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->middleware('permission:departments.update')->name('departments.edit');
Route::put('departments/{department}', [DepartmentController::class, 'update'])->middleware('permission:departments.update')->name('departments.update');
Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:departments.delete')->name('departments.destroy');
Route::get('programs', [ProgramController::class, 'index'])->middleware('permission:programs.read')->name('programs.index');
Route::get('programs/create', [ProgramController::class, 'create'])->middleware('permission:programs.create')->name('programs.create');
Route::post('programs', [ProgramController::class, 'store'])->middleware('permission:programs.create')->name('programs.store');
Route::get('programs/{program}/edit', [ProgramController::class, 'edit'])->middleware('permission:programs.update')->name('programs.edit');
Route::put('programs/{program}', [ProgramController::class, 'update'])->middleware('permission:programs.update')->name('programs.update');
Route::delete('programs/{program}', [ProgramController::class, 'destroy'])->middleware('permission:programs.delete')->name('programs.destroy');
Route::get('subjects', [SubjectController::class, 'index'])->middleware('permission:subjects.read')->name('subjects.index');
Route::get('subjects/create', [SubjectController::class, 'create'])->middleware('permission:subjects.create')->name('subjects.create');
Route::post('subjects', [SubjectController::class, 'store'])->middleware('permission:subjects.create')->name('subjects.store');
Route::get('subjects/{subject}/edit', [SubjectController::class, 'edit'])->middleware('permission:subjects.update')->name('subjects.edit');
Route::put('subjects/{subject}', [SubjectController::class, 'update'])->middleware('permission:subjects.update')->name('subjects.update');
Route::delete('subjects/{subject}', [SubjectController::class, 'destroy'])->middleware('permission:subjects.delete')->name('subjects.destroy');
Route::get('curricula', [CurriculumController::class, 'index'])->middleware('permission:curriculum.read')->name('curricula.index');
Route::get('curricula/create', [CurriculumController::class, 'create'])->middleware('permission:curriculum.create')->name('curricula.create');
Route::post('curricula', [CurriculumController::class, 'store'])->middleware('permission:curriculum.create')->name('curricula.store');
Route::get('curricula/{curriculum}/edit', [CurriculumController::class, 'edit'])->middleware('permission:curriculum.update')->name('curricula.edit');
Route::put('curricula/{curriculum}', [CurriculumController::class, 'update'])->middleware('permission:curriculum.update')->name('curricula.update');
Route::delete('curricula/{curriculum}', [CurriculumController::class, 'destroy'])->middleware('permission:curriculum.delete')->name('curricula.destroy');

Route::get('students', [StudentController::class, 'index'])->middleware('permission:students.read')->name('students.index');
Route::post('students/filters', [SavedFilterController::class, 'storeStudents'])->middleware('permission:students.read')->name('students.filters.store');
Route::delete('students/filters/{savedFilter}', [SavedFilterController::class, 'destroyStudents'])->middleware('permission:students.read')->name('students.filters.destroy');
Route::get('students/create', [StudentController::class, 'create'])->middleware('permission:students.create')->name('students.create');
Route::post('students', [StudentController::class, 'store'])->middleware('permission:students.create')->name('students.store');
Route::get('students/export', [StudentController::class, 'export'])->middleware(['permission:students.read', 'throttle:exports'])->name('students.export');
Route::get('students/{student}/edit', [StudentController::class, 'edit'])->middleware('permission:students.update')->name('students.edit');
Route::put('students/{student}', [StudentController::class, 'update'])->middleware('permission:students.update')->name('students.update');
Route::delete('students/{student}', [StudentController::class, 'destroy'])->middleware('permission:students.delete')->name('students.destroy');
Route::post('students/{student}/class-subjects/{classSubject}/drop', [StudentController::class, 'dropClassSubjectEnrollment'])->middleware('permission:enrollments.update')->name('students.class-subjects.drop');
Route::post('students/{student}/documents', [StudentController::class, 'uploadDocument'])->middleware('permission:student_documents.create')->name('students.documents.upload');
Route::get('documents/{document}/download', [StudentController::class, 'downloadDocument'])->middleware('permission:student_documents.read')->name('students.documents.download');
Route::delete('documents/{document}', [StudentController::class, 'destroyDocument'])->middleware('permission:student_documents.delete')->name('students.documents.destroy');
Route::get('students/{student}/report-card', [ExamController::class, 'studentReportCard'])->middleware('permission:report_card.read')->name('students.report-card');
Route::get('students/{student}/report-card/pdf', [ExamController::class, 'downloadStudentReportCard'])->middleware('permission:report_card.read')->name('students.report-card.pdf');

Route::get('teachers', [TeacherController::class, 'index'])->middleware('permission:staff.read')->name('teachers.index');

Route::get('guardians', [GuardianController::class, 'index'])->middleware('permission:guardians.read')->name('guardians.index');
Route::get('guardians/create', [GuardianController::class, 'create'])->middleware('permission:guardians.create')->name('guardians.create');
Route::post('guardians', [GuardianController::class, 'store'])->middleware('permission:guardians.create')->name('guardians.store');
Route::get('guardians/{guardian}/edit', [GuardianController::class, 'edit'])->middleware('permission:guardians.update')->name('guardians.edit');
Route::put('guardians/{guardian}', [GuardianController::class, 'update'])->middleware('permission:guardians.update')->name('guardians.update');
Route::delete('guardians/{guardian}', [GuardianController::class, 'destroy'])->middleware('permission:guardians.delete')->name('guardians.destroy');

Route::get('classes', [SchoolClassController::class, 'index'])->middleware('permission:classes.read')->name('classes.index');
Route::get('classes/create', [SchoolClassController::class, 'create'])->middleware('permission:classes.create')->name('classes.create');
Route::post('classes', [SchoolClassController::class, 'store'])->middleware('permission:classes.create')->name('classes.store');
Route::get('classes/{schoolClass}/edit', [SchoolClassController::class, 'edit'])->middleware('permission:classes.update')->name('classes.edit');
Route::put('classes/{schoolClass}', [SchoolClassController::class, 'update'])->middleware('permission:classes.update')->name('classes.update');
Route::delete('classes/{schoolClass}', [SchoolClassController::class, 'destroy'])->middleware('permission:classes.delete')->name('classes.destroy');

Route::get('class-subjects', [ClassSubjectController::class, 'index'])->middleware('permission:class_subjects.read')->name('class-subjects.index');
Route::post('class-subjects', [ClassSubjectController::class, 'store'])->middleware('permission:class_subjects.create')->name('class-subjects.store');
Route::delete('class-subjects/{classSubject}', [ClassSubjectController::class, 'destroy'])->middleware('permission:class_subjects.delete')->name('class-subjects.destroy');

Route::get('timetable', [TimetableController::class, 'index'])->middleware('permission:timetable.read')->name('timetable.index');
Route::post('timetable', [TimetableController::class, 'store'])->middleware('permission:timetable.create')->name('timetable.store');
Route::delete('timetable/{timetableSlot}', [TimetableController::class, 'destroy'])->middleware('permission:timetable.delete')->name('timetable.destroy');

Route::get('attendance', [AttendanceController::class, 'index'])->middleware('permission:attendance.read')->name('attendance.index');
Route::get('attendance/mark', [AttendanceController::class, 'create'])->middleware('permission:attendance.read')->name('attendance.create');
Route::post('attendance', [AttendanceController::class, 'store'])->middleware('permission:attendance.update')->name('attendance.store');

Route::get('exams', [ExamController::class, 'index'])->middleware('permission:exams.read')->name('exams.index');
Route::get('exams/create', [ExamController::class, 'create'])->middleware('permission:exams.create')->name('exams.create');
Route::post('exams', [ExamController::class, 'store'])->middleware('permission:exams.create')->name('exams.store');
Route::get('exams/{exam}/edit', [ExamController::class, 'edit'])->middleware('permission:exams.update')->name('exams.edit');
Route::put('exams/{exam}', [ExamController::class, 'update'])->middleware('permission:exams.update')->name('exams.update');
Route::delete('exams/{exam}', [ExamController::class, 'destroy'])->middleware('permission:exams.delete')->name('exams.destroy');
Route::get('exams/{exam}/results', [ExamController::class, 'results'])->middleware('permission:results.read')->name('exams.results');
Route::post('exams/{exam}/publish', [ExamController::class, 'publish'])->middleware('permission:results.publish')->name('exams.publish');
Route::post('exams/{exam}/unpublish', [ExamController::class, 'unpublish'])->middleware(['permission:results.publish', 'role:admin'])->name('exams.unpublish');
Route::get('term-results', [TermResultController::class, 'index'])->middleware('permission:results.read')->name('term-results.index');
Route::post('terms/{term}/results/generate', [TermResultController::class, 'generate'])->middleware('permission:results.approve')->name('term-results.generate');
Route::post('terms/{term}/results/submit', [TermResultController::class, 'submit'])->middleware('permission:results.approve')->name('term-results.submit');
Route::post('terms/{term}/results/publish', [TermResultController::class, 'publish'])->middleware('permission:results.publish')->name('term-results.publish');
Route::post('terms/{term}/results/unpublish', [TermResultController::class, 'unpublish'])->middleware(['permission:results.publish', 'role:admin'])->name('term-results.unpublish');
Route::get('grade-scale', [GradeScaleController::class, 'edit'])->middleware('permission:grades.read')->name('grade-scale.edit');
Route::put('grade-scale', [GradeScaleController::class, 'update'])->middleware('permission:grades.update')->name('grade-scale.update');

Route::get('fee-structures', [FeeStructureController::class, 'index'])->middleware('permission:fee_structures.read')->name('fee-structures.index');
Route::post('fee-structures', [FeeStructureController::class, 'store'])->middleware('permission:fee_structures.create')->name('fee-structures.store');
Route::put('fee-structures/{feeStructure}', [FeeStructureController::class, 'update'])->middleware('permission:fee_structures.update')->name('fee-structures.update');
Route::delete('fee-structures/{feeStructure}', [FeeStructureController::class, 'destroy'])->middleware('permission:fee_structures.delete')->name('fee-structures.destroy');
Route::get('invoices', [InvoiceController::class, 'index'])->middleware('permission:invoices.read')->name('invoices.index');
Route::post('invoices/filters', [SavedFilterController::class, 'storeInvoices'])->middleware('permission:invoices.read')->name('invoices.filters.store');
Route::delete('invoices/filters/{savedFilter}', [SavedFilterController::class, 'destroyInvoices'])->middleware('permission:invoices.read')->name('invoices.filters.destroy');
Route::get('invoices/create', [InvoiceController::class, 'create'])->middleware('permission:invoices.create')->name('invoices.create');
Route::post('invoices', [InvoiceController::class, 'store'])->middleware('permission:invoices.create')->name('invoices.store');
Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.read')->name('invoices.show');
Route::post('invoices/issue-drafts', [InvoiceController::class, 'issueDrafts'])->middleware('permission:invoices.update')->name('invoices.issue-drafts');
Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->middleware('permission:invoices.update')->name('invoices.issue');
Route::post('fee-structures/{feeStructure}/items', [FeeStructureController::class, 'storeItem'])->middleware('permission:fee_structures.update')->name('fee-structures.items.store');
Route::delete('fee-items/{feeItem}', [FeeStructureController::class, 'destroyItem'])->middleware('permission:fee_structures.update')->name('fee-items.destroy');
Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->middleware('permission:invoices.update')->name('invoices.cancel');
Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->middleware('permission:invoices.read')->name('invoices.pdf');
Route::get('payments/create', [PaymentController::class, 'create'])->middleware('permission:payments.create')->name('payments.create');
Route::post('payments', [PaymentController::class, 'store'])->middleware('permission:payments.create')->name('payments.store');
Route::get('receipts/{payment}', [PaymentController::class, 'receipt'])->middleware('permission:payments.read')->name('receipt.show');
Route::get('receipts/{payment}/pdf', [PaymentController::class, 'receiptPdf'])->middleware('permission:payments.read')->name('receipt.pdf');
Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])->middleware(['role:super-admin'])->name('payments.reverse');

Route::get('announcements', [AnnouncementController::class, 'index'])->middleware('permission:announcements.read')->name('announcements.index');
Route::get('announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->middleware('permission:announcements.update')->name('announcements.edit');
Route::post('announcements', [AnnouncementController::class, 'store'])->middleware('permission:announcements.create')->name('announcements.store');
Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])->middleware('permission:announcements.update')->name('announcements.update');
Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware(['permission:announcements.delete', 'role:admin'])->name('announcements.destroy');
Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware(['permission:audit_logs.read', 'role:admin'])->name('audit-logs.index');

require base_path('routes/accounting.php');
