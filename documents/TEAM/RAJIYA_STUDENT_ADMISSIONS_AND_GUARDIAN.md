# Team Member: RAJIYA
## Domain: Student Lifecycle, Admissions, Registrar & Guardian Portal

**Assigned Engineer:** Rajiya  
**System Role:** Registrar / Student Affairs / Guardian Portal Lead  
**Deliverable Zip File:** `RAJIYA_STUDENT_PACKAGE.zip` (located in `docs/TEAM/packages/` and `documents/TEAM/packages/`)  

---

### 1. Functional Scope & Key Responsibilities

Rajiya owns the complete student lifecycle from initial admission to academic progression and family portal engagement:
1. **Student Master Information Directory (SIS) & Admissions (`/admin/students`):**
   - Sequential, unique admission number generation via `AdmissionNumberGenerator`.
   - Student onboarding, profile management, and document uploads (`StudentDocument`).
   - Class capacity enforcement blocking enrollment when class size limit is reached.
2. **Guardian / Parent Family Portal (`/guardian/dashboard`):**
   - Multi-child linking allowing parents with multiple enrolled children to switch context seamlessly.
   - Live view of child attendance, published term results, and fee statements.
3. **Student Self-Service Portal (`/student/dashboard`):**
   - Student academic profile, personal class timetable, and published results.
   - Elective course registration with capacity checks (`/student/electives`).
4. **Academic Catalog & Hierarchy:**
   - Academic Years and Term management (ensuring single active year and term invariants).
   - Academic Departments, Degree/Certificate Programs, Curricula, and Subjects.
   - Cohort Classes & Sections management.
5. **Student Operational Logistics:**
   - Hostel boarding and room allotment (`HostelAssignmentService`).
   - Transport fleet logistics and student bus route assignments (`TransportService`).
   - Cafeteria meal catalogue and student pre-ordering (`CafeteriaOrderService`).
6. **Official Academic Transcripts (`/transcripts`):**
   - Official multi-term academic transcripts with cumulative CGPA computation and PDF download.

---

### 2. Complete File Inventory (103 Files)

#### Routes:
* `routes/student.php`
* `routes/guardian.php`
* `routes/registrar.php`

#### Controllers:
* `app/Http/Controllers/Student/DashboardController.php`
* `app/Http/Controllers/Student/ProfileController.php`
* `app/Http/Controllers/Student/AttendanceController.php`
* `app/Http/Controllers/Student/ResultController.php`
* `app/Http/Controllers/Student/TimetableController.php`
* `app/Http/Controllers/Student/ElectiveController.php`
* `app/Http/Controllers/Student/HostelController.php`
* `app/Http/Controllers/Student/CafeteriaController.php`
* `app/Http/Controllers/Guardian/DashboardController.php`
* `app/Http/Controllers/Guardian/ChildAttendanceController.php`
* `app/Http/Controllers/Guardian/ChildResultController.php`
* `app/Http/Controllers/Guardian/ChildHostelController.php`
* `app/Http/Controllers/Guardian/InvoiceController.php`
* `app/Http/Controllers/Registrar/DashboardController.php`
* `app/Http/Controllers/InstitutionAdmin/StudentController.php`
* `app/Http/Controllers/InstitutionAdmin/GuardianController.php`
* `app/Http/Controllers/InstitutionAdmin/AcademicYearController.php`
* `app/Http/Controllers/InstitutionAdmin/TermController.php`
* `app/Http/Controllers/InstitutionAdmin/DepartmentController.php`
* `app/Http/Controllers/InstitutionAdmin/ProgramController.php`
* `app/Http/Controllers/InstitutionAdmin/CurriculumController.php`
* `app/Http/Controllers/InstitutionAdmin/SchoolClassController.php`
* `app/Http/Controllers/InstitutionAdmin/SubjectController.php`
* `app/Http/Controllers/InstitutionAdmin/HostelController.php`
* `app/Http/Controllers/InstitutionAdmin/TransportController.php`
* `app/Http/Controllers/InstitutionAdmin/CafeteriaController.php`
* `app/Http/Controllers/TranscriptController.php`

#### Form Requests:
* `app/Http/Requests/InstitutionAdmin/StoreStudentRequest.php`
* `app/Http/Requests/InstitutionAdmin/UpdateStudentRequest.php`

#### Domain Services & Support:
* `app/Services/EnrollmentService.php`
* `app/Services/TranscriptService.php`
* `app/Services/ElectiveRegistrationService.php`
* `app/Services/HostelAssignmentService.php`
* `app/Services/TransportService.php`
* `app/Services/CafeteriaOrderService.php`
* `app/Support/AdmissionNumberGenerator.php`

#### Models:
* `app/Models/Student.php`
* `app/Models/StudentDocument.php`
* `app/Models/Guardian.php`
* `app/Models/AcademicYear.php`
* `app/Models/Term.php`
* `app/Models/Department.php`
* `app/Models/Program.php`
* `app/Models/Curriculum.php`
* `app/Models/SchoolClass.php`
* `app/Models/Subject.php`
* `app/Models/Hostel.php`
* `app/Models/HostelRoom.php`
* `app/Models/HostelAssignment.php`
* `app/Models/TransportRoute.php`
* `app/Models/TransportVehicle.php`
* `app/Models/TransportStop.php`
* `app/Models/StudentTransportAssignment.php`
* `app/Models/CafeteriaItem.php`
* `app/Models/CafeteriaOrder.php`
* `app/Models/CafeteriaOrderItem.php`

#### Blade Views:
* `resources/views/Student/` (Dashboard, Profile, Attendance, Result, Timetable, Electives, Hostel, Cafeteria)
* `resources/views/Guardian/` (Dashboard, ChildSwitch, Attendance, Results, Hostel, Invoices)
* `resources/views/Registrar/` (Admissions Desk, Registration Stats)
* `resources/views/InstitutionAdmin/InstitutionAdminStudentsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminStudentsCreate.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminStudentsEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminStudentsForm.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminStudentsShow.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminGuardiansIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminGuardiansCreate.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminGuardiansEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminGuardiansForm.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminClassesIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminClassesCreate.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminClassesEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminClassesForm.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminAcademicYearsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminTermsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminDepartmentsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminDepartmentsCreate.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminDepartmentsEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminProgramsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminProgramsCreate.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminProgramsEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminCurriculaIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminCurriculaCreate.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminCurriculaEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminSubjectsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminSubjectsCreate.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminSubjectsEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminSubjectsForm.blade.php`
* `resources/views/InstitutionAdmin/HostelIndex.blade.php`
* `resources/views/InstitutionAdmin/HostelAssignmentForm.blade.php`
* `resources/views/InstitutionAdmin/TransportIndex.blade.php`
* `resources/views/InstitutionAdmin/TransportAssignmentForm.blade.php`
* `resources/views/InstitutionAdmin/TransportRouteForm.blade.php`
* `resources/views/InstitutionAdmin/TransportVehicleForm.blade.php`
* `resources/views/InstitutionAdmin/CafeteriaIndex.blade.php`
* `resources/views/Pdf/PdfTranscript.blade.php`

#### Feature Test Suites:
* `tests/Feature/RegistrarAccessTest.php`
* `tests/Feature/EnrollmentAndMarksServiceTest.php`
* `tests/Feature/StudentAccountLinkingTest.php`
* `tests/Feature/CurriculumModuleTest.php`
* `tests/Feature/Phase1AuthAndAcademicTest.php`

---

### 3. Step-by-Step Verification Commands for Rajiya

```powershell
# Run all admissions, registrar, and academic catalog tests
php vendor/bin/phpunit tests/Feature/RegistrarAccessTest.php tests/Feature/EnrollmentAndMarksServiceTest.php tests/Feature/CurriculumModuleTest.php tests/Feature/Phase1AuthAndAcademicTest.php
```
