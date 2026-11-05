# Database Schema (schema.sql)

**Project:** IGK School ERP — Laravel 12.70.0  
**SOP Reference:** §11 DB Design, §29 Handover Artefact #2  
**Source of truth:** The 24 migration files in `database/migrations/` (Laravel migrations are the schema definition).

---

## Migration Inventory (24 migrations, applied in timestamp order)

| # | Migration File | Table(s) Created/Modified |
|---|---|---|
| 1 | `0001_01_01_000000_create_users_table.php` | `users` |
| 2 | `0001_01_01_000001_create_cache_table.php` | `cache` |
| 3 | `0001_01_01_000002_create_jobs_table.php` | `jobs`, `failed_jobs` |
| 4 | `2026_01_01_000001_add_role_to_users_table.php` | `users` (adds `role` column) |
| 5 | `2026_01_01_000002_create_classes_table.php` | `school_classes` |
| 6 | `2026_01_01_000003_create_subjects_table.php` | `subjects` |
| 7 | `2026_01_01_000004_create_teachers_table.php` | `teachers` |
| 8 | `2026_01_01_000005_create_guardians_table.php` | `guardians` |
| 9 | `2026_01_01_000006_create_students_table.php` | `students` |
| 10 | `2026_01_01_000007_create_class_subject_table.php` | `class_subjects` |
| 11 | `2026_01_01_000008_create_attendance_table.php` | `attendances` |
| 12 | `2026_01_01_000009_create_exams_table.php` | `exams` |
| 13 | `2026_01_01_000010_create_results_table.php` | `results` |
| 14 | `2026_01_01_000011_upgrade_core_tables_for_sop.php` | Core tables (capacity, soft deletes, etc.) |
| 15 | `2026_01_01_000012_create_student_documents_table.php` | `student_documents` |
| 16 | `2026_01_01_000013_create_timetable_slots_table.php` | `timetable_slots` |
| 17 | `2026_01_01_000014_create_finance_tables.php` | `fee_structures`, `invoices`, `payments` |
| 18 | `2026_01_01_000015_create_announcements_table.php` | `announcements` |
| 19 | `2026_01_01_000016_create_audit_logs_table.php` | `audit_logs` |
| 20 | `2026_01_01_000017_create_rbac_tables.php` | Spatie RBAC tables (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) |
| 21 | `2026_01_01_000018_create_departments_table.php` | `departments` |
| 22 | `2026_01_01_000019_create_programs_table.php` | `programs` |
| 23 | `2026_01_01_000020_create_curricula_table.php` | `curricula` |
| 24 | `2026_01_01_000021_create_settings_table.php` | `settings` |

Plus Laravel's standard `migrations` table.

---

## Table Inventory (23 models)

| Model | Table | Notes |
|---|---|---|
| User | `users` | Auth, `role` column, `is_active`, `must_change_password` |
| AcademicYear | `academic_years` | `is_current` invariant (only one current at a time) |
| Term | `terms` | Belongs to academic year, `is_current` invariant |
| Department | `departments` | Academic department |
| Program | `programs` | Belongs to department |
| Subject | `subjects` | Belongs to program, `credit_hours` |
| Curriculum | `curricula` | Program-subject-term mapping, `is_elective` |
| SchoolClass | `school_classes` | `name`, `section`, `capacity` |
| ClassSubject | `class_subjects` | Class-subject-teacher assignment |
| Teacher | `teachers` | Staff profile linked to user |
| Student | `students` | Linked to user, class, guardian; `admission_no` |
| Guardian | `guardians` | Linked to user |
| StudentDocument | `student_documents` | Private storage, policy-controlled download |
| Enrollment | (via students/class_subjects) | Enrollment via student-class relationship |
| TimetableSlot | `timetable_slots` | Class-subject-day-time-room, clash detection |
| Attendance | `attendances` | Student-date-status, 48h edit window |
| Exam | `exams` | Class-subject-term, `max_marks`, `weight`, `is_published` |
| Result | `results` | Exam-student, `marks`, `is_absent` |
| FeeStructure | `fee_structures` | Class-term-name-amount |
| Invoice | `invoices` | Student-term, `invoice_no`, `total`, `balance`, `status` |
| Payment | `payments` | Invoice, amount, method, reference, receipt number |
| Announcement | `announcements` | `target_role`, `published_at`, `expires_at` |
| AuditLog | `audit_logs` | Spatie activity log mutations |
| Setting | `settings` | Key-value settings with type and group |
| GradeScale | `grade_scales` | Default grade scale |
| GradeScaleItem | `grade_scale_items` | Grade boundaries and points |

---

## Schema Conventions (SOP §11 compliance)

- Big increments for primary keys
- `created_at`/`updated_at` timestamps on all tables
- Soft deletes where history matters
- Enums as PHP enums for status fields
- `decimal(12,2)` for money (never float)
- Foreign keys with `restrict` by default; cascade only on true child rows
- Unique constraints on every business identifier (e.g. `admission_no`, `invoice_no`)
- Indexes on every foreign key and every filter/sort column

---

## Reproduction

A fresh `php artisan migrate --seed` reproduces the schema exactly:

```bash
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
```
