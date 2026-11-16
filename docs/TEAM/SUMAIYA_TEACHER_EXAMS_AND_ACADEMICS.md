# Team Member: SUMAIYA
## Domain: Teacher Desk, Marks Entry, Examinations & Academic Grading

**Assigned Engineer:** Sumaiya  
**System Role:** Teacher / Examination Officer / Academic Faculty  
**Deliverable Zip File:** `SUMAIYA_TEACHER_PACKAGE.zip` (located in `docs/TEAM/packages/` and `documents/TEAM/packages/`)  

---

### 1. Functional Scope & Key Responsibilities

Sumaiya owns the daily teaching desk, student examination workflows, marks evaluation, grading engines, and academic attendance roll calls:
1. **Teacher Desk Portal (`/teacher/dashboard`):**
   - Teacher assigned classes and class-subject combinations.
   - Real-time weekly timetable view with period intervals and room allocations.
   - Teacher self-service profile (`/teacher/profile`) displaying assigned teaching load and salary transparent breakdown.
2. **Attendance Roll Call & Biometric Monitoring (`/teacher/attendance`):**
   - Rapid 1-click class roll call (Present, Absent, Late, Excused).
   - Real-time absentee parent notifications via SMS and Email digests.
   - Staff daily attendance & biometric device endpoint integration (`/api/v1/biometric-events`).
3. **Examination Management & Timetable Clash Detector (`/admin/exams`):**
   - Creation of exam papers per class, subject, and term with maximum marks and weightages.
   - Timetable slot clash detection preventing overlapping room or teacher allocations.
4. **Marks Entry Engine with Boundary Validation (`/teacher/marks`):**
   - Marks entry strictly scoped to the teacher's assigned subjects.
   - Hard boundary validation preventing negative marks or scores exceeding maximum marks.
5. **Grading Engine & Term Results Publishing (`/admin/term-results`):**
   - Automated conversion of marks to letter grades and GPA using configured `GradeScale`.
   - Batch GPA generation across all subjects.
   - Official result publication locking marks from further edits (with authorized unpublish audit logging).

---

### 2. Complete File Inventory (57 Files)

#### Routes:
* `routes/teacher.php`

#### Controllers:
* `app/Http/Controllers/Teacher/DashboardController.php`
* `app/Http/Controllers/Teacher/AttendanceController.php`
* `app/Http/Controllers/Teacher/ClassController.php`
* `app/Http/Controllers/Teacher/ExamController.php`
* `app/Http/Controllers/Teacher/MarkController.php`
* `app/Http/Controllers/Teacher/TimetableController.php`
* `app/Http/Controllers/Teacher/ProfileController.php`
* `app/Http/Controllers/InstitutionAdmin/TeacherController.php`
* `app/Http/Controllers/InstitutionAdmin/ClassSubjectController.php`
* `app/Http/Controllers/InstitutionAdmin/TimetableController.php`
* `app/Http/Controllers/InstitutionAdmin/ExamController.php`
* `app/Http/Controllers/InstitutionAdmin/MarkEntryController.php`
* `app/Http/Controllers/InstitutionAdmin/GradeScaleController.php`
* `app/Http/Controllers/InstitutionAdmin/ResultPublicationController.php`
* `app/Http/Controllers/InstitutionAdmin/TermResultController.php`
* `app/Http/Controllers/InstitutionAdmin/StaffAttendanceController.php`
* `app/Http/Controllers/Api/V1/BiometricEventController.php`

#### Domain Services:
* `app/Services/MarksEntryService.php`
* `app/Services/GradingService.php`
* `app/Services/GradeScaleService.php`
* `app/Services/TermResultService.php`
* `app/Services/ResultPublicationService.php`
* `app/Services/AttendanceSessionService.php`
* `app/Services/SmsService.php`
* `app/Services/ConfiguredHttpSmsGateway.php`

#### Models:
* `app/Models/Teacher.php`
* `app/Models/ClassSubject.php`
* `app/Models/TimetableSlot.php`
* `app/Models/Exam.php`
* `app/Models/Result.php`
* `app/Models/GradeScale.php`
* `app/Models/GradeScaleItem.php`
* `app/Models/TermResult.php`
* `app/Models/TermResultGeneration.php`
* `app/Models/Attendance.php`
* `app/Models/AttendanceSession.php`
* `app/Models/StaffAttendance.php`
* `app/Models/BiometricDevice.php`
* `app/Models/BiometricEvent.php`

#### Blade Views:
* `resources/views/Teacher/` (Dashboard, Attendance, Marks, Exams, Timetable, Profile)
* `resources/views/components/teacher-table.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminTeachersIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminTeachersCreate.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminTeachersEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminTeachersForm.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminClassSubjectsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminTimetableIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminExamsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminExamsForm.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminGradeScaleEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminTermResultsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminStaffAttendanceIndex.blade.php`

#### Feature Test Suites:
* `tests/Feature/Phase3MarksLockGpaPublicationReportCardTest.php`
* `tests/Feature/Phase2DocumentsCapacityTimetableAttendanceTest.php`
* `tests/Feature/TeacherSubjectAssignmentSeederTest.php`
* `tests/Feature/TermResultsWorkflowTest.php`

---

### 3. Step-by-Step Verification Commands for Sumaiya

```powershell
# Run mark entry, GPA publication and timetable tests
php vendor/bin/phpunit tests/Feature/Phase3MarksLockGpaPublicationReportCardTest.php tests/Feature/Phase2DocumentsCapacityTimetableAttendanceTest.php tests/Feature/TermResultsWorkflowTest.php
```
