# IGK SCHOOL ERP - MANUAL TEST SEEDER VERIFICATION REPORT

**Generated:** 2026-11-02  
**Target DB:** archi_manual_test (migrations done)  
**Protected:** archi (untouched)

## Seeders Inspected
- RolePermissionSeeder: creates 6 roles/permissions, assignments per SOP matrix
- SuperAdminSeeder: creates superadmin@school.test
- SettingSeeder: creates settings
- GradeScaleSeeder: creates default grade scale
- DatabaseSeeder: orchestrates + demo data

## Roles/Users/Data Created
6 MVP roles, permissions assigned, admin/superadmin users, academic year/term, classes/sections, students/guardians/teachers, timetable/attendance/exams/results, finance data, announcements.

## Safety
No TRUNCATE/DELETE found. firstOrCreate/updateOrCreate used mostly; DatabaseSeeder/GradeScaleSeeder use create() - safe once on empty DB.

## Recommended Order
Run db:seed (DatabaseSeeder calls RolePermissionSeeder) or seed in order: RolePermissionSeeder, SettingSeeder, GradeScaleSeeder, SuperAdminSeeder, DatabaseSeeder.

## Commands (archi_manual_test ONLY)
`powershell
="archi_manual_test"; php artisan config:clear
php artisan config:show database | Select-String -Pattern "mysql.*database"  # must be archi_manual_test
php artisan db:seed --force
Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue; php artisan config:clear
php artisan config:show database | Select-String -Pattern "mysql.*database"  # must be archi
`

**No seeders executed. No changes made.**
