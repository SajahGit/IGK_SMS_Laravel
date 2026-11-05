# IGK ERP System Architecture and Workflow

## Overview
Laravel school management ERP. PHP 8.5, Laravel 11, MySQL (archi DB). Blade UI, Spatie permission. Roles: Super Admin, Institution Admin, Registrar, Teacher, Accountant, Student.

## Request Lifecycle
Browser -> public/index.php -> bootstrap/app.php -> HTTP kernel -> middleware (auth, role, policy) -> routes/web.php -> controller -> form request validation -> service -> Eloquent model -> MySQL -> Blade view response.

## Modules
See [IGK_ERP_COMPLETE_MODULE_COMPLIANCE_MATRIX.md](../IGK_ERP_COMPLETE_MODULE_COMPLIANCE_MATRIX.md) for the current implementation snapshot. This architecture note is historical; see `docs/architecture.md` for current architecture.

## Database
Tables: users, roles, permissions, model_has_roles, academic_years, terms, departments, programs, subjects, curriculums, classes, sections, staff, students, guardians, student_documents, class_subjects, enrollments, timetables, attendance, attendance_sessions, exams, exam_subjects, marks, grade_scales, results, fee_structures, invoices, payments, receipts, announcements, audit_logs, sessions, cache, jobs, failed_jobs, password_reset_tokens, personal_access_tokens.

## Workflows
Academic year creation -> terms -> departments/programs/subjects/curriculum -> classes/sections -> staff -> students/guardians/documents -> offerings -> enrollment -> timetable -> attendance -> exams -> marks -> grading -> results publication -> report card. Finance: fee structures -> invoices -> payments -> receipts -> reversal -> reports. Announcements and audit logs cross-cutting.

## Roles
Super Admin (all), Institution Admin (all except roles/permissions), Registrar (academic), Teacher (assigned classes), Accountant (finance), Student (own).

## Troubleshooting
- Login failure: verify seeder ran, password hash, is_active.
- Missing roles: run RolePermissionSeeder.
- Missing sessions table: migrate.
- Missing APP_KEY: key:generate.
- Storage link: storage:link.
- Cache: config:clear, cache:clear, route:clear, view:clear.
