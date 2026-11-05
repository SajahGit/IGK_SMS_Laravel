# IGK ERP SOP Requirements Traceability

Project: E:\Laravel-Projects\Architectured Final S
SOP: E:\Fathi\Sajah Sir's  archiecture\sms-49day-laravel-sop.html

## MVP Scope Summary
Per SOP §4, MVP includes modules M1-M26 (438h). Post-MVP: Library, multi-campus, transcripts/CGPA, online payments, internal messaging, HR/payroll, hostel/transport/inventory, public API, elective registration, biometric, SMS, multi-language, legacy migration, mobile app (§4.3).

## Requirement Traceability Table

| SOP Section | Requirement | Implementation Evidence | Status |
|---|---|---|---|
| §4 M1 | Auth login/logout/password reset/forced change | app/Http/Controllers/Auth/*, tests/Feature/Phase1AuthAndAcademicTest | Implemented |
| §4 M2 | RBAC roles/permissions/policies and route enforcement | database/seeders/RolePermissionSeeder.php, app/Policies/*, routes/modules/admin.php, routes/registrar.php, tests/Feature/RegistrarAccessTest.php, tests/Feature/RoleLoginVerificationTest.php | Role boundaries are automated-tested and six local role dashboards were browser-checked; the SOP states 78 permissions while the seeder currently defines 110 unique names, requiring an approved catalog reconciliation |
| §4 M3 | User management CRUD | app/Http/Controllers/InstitutionAdmin/UserController.php, routes/modules/admin.php | Institution Admin user CRUD; Registrar read scope is permission-guarded; Super Admin-only access to Super Admin accounts and role assignment |
| §4 M4 | Institution settings + academic years/terms | app/Models/AcademicYear.php, Term.php | Implemented |
| §4 M5 | Departments/programs/subjects/curriculum | app/Models/Department.php, Program.php, Subject.php, Curriculum.php | Implemented |
| §4 M6 | Classes/sections capacity | app/Models/SchoolClass.php, app/Http/Controllers/InstitutionAdmin/SchoolClassController.php | Implemented |
| §4 M7 | Staff profiles/teachers | app/Models/Teacher.php, app/Http/Controllers/InstitutionAdmin/TeacherController.php, migration `2026_01_01_000023_complete_sop_academic_relationships.php` | Department/status fields and assignments implemented; detailed teacher-scope workflow acceptance remains |
| §4 M8 | Student admission/profile/admission number | app/Services/EnrollmentService.php | Implemented |
| §4 M9 | Many-to-many guardians, primary contact, and student documents | app/Models/Guardian.php, app/Models/Student.php, migration `2026_01_01_000023_complete_sop_academic_relationships.php` | Many-to-many links, primary contact, legacy-link backfill, and documents implemented; multiple-guardian UAT pending |
| §4 M10 | Student search/filter/pagination/export | app/Http/Controllers/InstitutionAdmin/StudentController.php, tests/Feature/StudentRegisterExportTest.php | Implemented and tested for filtered CSV export |
| §4 M11 | Class-subject offerings/teacher assignment | app/Models/ClassSubject.php, app/Http/Controllers/InstitutionAdmin/ClassSubjectController.php | Implemented |
| §4 M12 | Enrollment + automatic subject enrollment + drop | app/Services/EnrollmentService.php, app/Models/Student.php, app/Models/ClassSubject.php, migration `2026_01_01_000023_complete_sop_academic_relationships.php` | Term-aware class offerings and student-subject enrollment implemented; promotion/drop acceptance walkthrough pending |
| §4 M13 | Timetable clash detection | app/Support/TimetableClashDetector.php, tests/Feature/Phase2DocumentsCapacityTimetableAttendanceTest.php | Implemented |
| §4 M14 | Per-class-session attendance/edit window/summaries | app/Models/Attendance.php, app/Models/AttendanceSession.php, app/Services/AttendanceSessionService.php, tests/Feature/ReportsParityTest.php | Session-based attendance, scope, edit window, remarks, excused status, legacy-data backfill, and summaries implemented; manual acceptance pending |
| §4 M15 | Exams/exam subjects | app/Models/Exam.php, ExamSubject.php | Implemented |
| §4 M16 | Marks entry bulk/absent/lock | app/Services/MarksEntryService.php | Implemented |
| §4 M17 | Grade scale/GPA engine/term results | app/Services/GradingService.php, app/Services/GradeScaleService.php, app/Services/TermResultService.php, app/Models/SubjectResult.php, app/Models/TermResult.php, tests/Feature/TermResultsWorkflowTest.php | Configurable scale, persisted idempotent term results, absence handling, dropped enrollment exclusion, missing-pair diagnostics, and >200-student queue dispatch implemented and tested |
| §4 M18 | Result approval/publication/report card PDF | app/Services/TermResultService.php, app/Http/Controllers/InstitutionAdmin/TermResultController.php, app/Support/ReportCardData.php, resources/views/pdf/report-card.blade.php | Term approval/publication, locking, audit, student/guardian notifications, and persisted-result report-card integration implemented; real local database queue worker completed a generation job; production worker checks remain |
| §4 M19 | Fee structures | app/Models/FeeStructure.php | Implemented |
| §4 M20 | Invoice generation bulk/sequential | tests/Feature/Phase4FinanceModuleTest | Implemented |
| §4 M21 | Payments/receipts/reversal | app/Models/Payment.php, Receipt.php | Implemented |
| §4 M22 | Announcements with audience targeting and delivery | app/Models/Announcement.php, app/Http/Controllers/InstitutionAdmin/AnnouncementController.php, app/Services/AnnouncementDeliveryService.php, database/migrations/2026_01_01_000028_repair_notifications_table.php, tests/Feature/Phase5AnnouncementsAuditReportsTest.php, tests/Feature/AccountantFinanceAccessTest.php | Role/program/class targeting, database notifications, queued email, scheduled delivery, and the previously missing local notification schema are covered; focused unread-dropdown regression test passes; business workflow sign-off remains |
| §4 M23 | Role dashboards and role-scoped access | app/Http/Controllers/Registrar/DashboardController.php, resources/views/registrar/Dashboard.blade.php, routes/modules/admin.php, tests/Feature/RegistrarAccessTest.php | Six seeded roles reached their dashboards in local browser UAT; Super Admin role management and Institution Admin denial verified; detailed workflow sign-off remains |
| §4 M24 | 11 reports with Appendix A filters/columns and CSV | app/Http/Controllers/Report/*, routes/reports.php, tests/Feature/ReportsParityTest.php | R1-R11 filters/columns reviewed; CSV headers aligned with visible tables and regression-tested. Product-owner sign-off and SOP-sized performance checks remain |
| §4 M25 | Audit log viewer/filters | app/Models/AuditLog.php | Implemented |
| §4 M26 | UI foundation Blade components | resources/views/components/* | Implemented |
| §7 | Six core roles plus the read-only Guardian portal | app/Http/Controllers/{SuperAdmin,InstitutionAdmin,Registrar,Teacher,Accountant,Student,Guardian}, app/Http/Requests/{SuperAdmin,InstitutionAdmin,Accountant,Teacher}, resources/views/{super-admin,institution-admin,registrar,teacher,accountant,student,guardian}, database/seeders/RolePermissionSeeder.php | Controllers, requests, and views are separated by role; shared admin URLs retain their route names. Six role dashboards and Super Admin/Institution Admin role-management boundary were browser-checked; full workflow UAT remains |
| §A9 | Parent records only, not spatie role | database/seeders/DatabaseSeeder.php | Implemented |
| §19 | Security: policies, audit, soft deletes | app/Policies/*, app/Models/AuditLog.php, middleware | Partial: code controls exist; security review evidence and full role-boundary verification are outstanding |
| §27 | Deployment SOP | docs/guides/deploy.md, docs/guides/backup-restore.md | Disposable SQLite migration/seed, production asset build, and local queue worker rehearsed; production deployment and backup/restore not evidenced |

Automated validation at latest full run: 201 tests passed (965 assertions, 1 skipped) after removing the non-SOP cashbook application feature. Pint passed for changed route files. All six seeded role dashboards and one real local database queue job were verified using a disposable SQLite database. The notification and finance cashbook migrations were applied to the configured local manual-test database without seeding or destructive refresh. Cashbook routes/screens/code/tests are removed; its applied migration/table remain untouched to preserve history and data. This does not constitute full workflow acceptance, measured coverage, security review, SOP-sized performance validation, business-owner sign-off, or production deployment approval.

## Post-MVP (not implemented, per SOP §4.3)
Library, multi-campus, transcripts/CGPA, online payment gateway, internal messaging, HR/payroll, hostel/transport/inventory/cafeteria, public REST API, elective registration, biometric attendance, SMS, multi-language/RTL, legacy data migration, mobile app.

## Business Proposal capabilities classification
1. Academic Management – MVP (M4-M6,M11,M13)
2. Student Administration – MVP (M8-M10)
3. Finance and Accounting – MVP (M19-M21,M24)
4. Fees and Payments – MVP (M19-M21)
5. HR and Payroll – Post-MVP
6. Inventory and Stock – Post-MVP
7. Procurement – Post-MVP
8. Multi-Branch Management – Post-MVP (campus_id reserved)
9. Library – Post-MVP
10. Transportation – Post-MVP
11. Communication – MVP announcements (M22); internal messaging Post-MVP
12. Parent Portal – SHOULD S1 (Post-MVP unless checkpoint)
13. Student Portal – MVP (student dashboard/results)
14. LMS – Post-MVP
15. Reporting – MVP (M24)
