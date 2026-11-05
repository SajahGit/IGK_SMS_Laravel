# IGK SCHOOL ERP - MANUAL TEST DATABASE SETUP PLAN

**Generated:** 2026-11-02  
**Project Path:** E:\Laravel-Projects\Architectured Final S  
**Target Test DB:** archi_manual_test (separate MySQL schema - created)  
**Production/Dev DB:** archi (MUST remain untouched)  

## 1. MIGRATION FILES (24 total) - Execution Order
1. 0001_01_01_000000_create_users_table.php
2. 0001_01_01_000001_create_cache_table.php
3. 0001_01_01_000002_create_jobs_table.php
4. 2026_01_01_000001_add_role_to_users_table.php
5. 2026_01_01_000002_create_classes_table.php
6. 2026_01_01_000003_create_subjects_table.php
7. 2026_01_01_000004_create_teachers_table.php
8. 2026_01_01_000005_create_guardians_table.php
9. 2026_01_01_000006_create_students_table.php
10. 2026_01_01_000007_create_class_subject_table.php
11. 2026_01_01_000008_create_attendance_table.php
12. 2026_01_01_000009_create_exams_table.php
13. 2026_01_01_000010_create_results_table.php
14. 2026_01_01_000011_upgrade_core_tables_for_sop.php
15. 2026_01_01_000012_create_student_documents_table.php
16. 2026_01_01_000013_create_timetable_slots_table.php
17. 2026_01_01_000014_create_finance_tables.php
18. 2026_01_01_000015_create_announcements_table.php
19. 2026_01_01_000016_create_audit_logs_table.php
20. 2026_01_01_000017_create_rbac_tables.php (spatie)
21. 2026_01_01_000018_create_departments_table.php
22. 2026_01_01_000019_create_programs_table.php
23. 2026_01_01_000020_create_curricula_table.php
24. 2026_01_01_000021_create_settings_table.php

## 2. SEEDERS
| Seeder | Responsibility |
|---|---|
| RolePermissionSeeder | Creates 6 roles, permissions, assigns per SOP matrix |
| SuperAdminSeeder | Creates superadmin@school.test with Super Admin role |
| SettingSeeder | Settings |
| GradeScaleSeeder | Grade scales |
| DatabaseSeeder | Orchestrates RolePermissionSeeder; creates academic year/term, System Admin, classes/subjects/teachers, demo students/guardians, timetable/attendance/exams/results, finance data via PaymentService, announcements, syncs roles |

## 3. WHAT IS CREATED
- 6 MVP roles + permissions (Spatie)
- Users (System Admin admin@school.test, Super Admin superadmin@school.test per seeders)
- Academic year/term (2026/Term 1)
- Classes/sections, departments/programs/subjects/curriculum structure as seeded
- Teachers, students, guardians (demo data)
- Timetable, attendance, exams/results, fee structures/invoices/payments, announcements

## 4. SAFETY NOTES
- No truncation/deletion logic obvious in inspected seeders, but still dangerous if run against wrong DB
- Foreign keys require migrations in order
- Do NOT run migrate:fresh/migrate:refresh/db:wipe

## 5. EXACT COMMANDS (archi_manual_test ONLY)
`powershell
# Create DB (done via MySQL)
# Temporarily point
="archi_manual_test"; php artisan config:clear
# Verify
php artisan config:show database | Select-String -Pattern "database"
# Migrate (only if needed)
="archi_manual_test"; php artisan config:clear; php artisan migrate --force
# Seed (only if needed)
="archi_manual_test"; php artisan config:clear; php artisan db:seed
# Restore
Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue; php artisan config:clear
`
Never modify .env. Never touch archi.

## 6. RISKS & MITIGATIONS
- Wrong DB: always verify with config:show
- Accidental migration/seeding of archi: never run without override and confirmation
- Tests isolated (SQLite in-memory)

**Plan Created:** Read-only inspection complete. No changes made to system.
