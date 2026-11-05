# IGK SCHOOL ERP - MANUAL TEST SEEDING EXECUTION REPORT

**Generated:** 2026-11-02  
**Target:** archi_manual_test | **Protected:** archi (untouched)

## Procedure Followed
- Read seeder verification report
- Applied ="archi_manual_test", cleared config
- Verified target was archi_manual_test (not archi)
- No .env changes, no migrations re-run, no migrate:fresh/refresh/wipe

## Execution
php artisan db:seed --force
- RolePermissionSeeder: SUCCESS (1,035 ms) - 6 roles/permissions created
- DatabaseSeeder failed at AcademicYear creation: Duplicate entry '2026' for key academic_years.academic_years_name_unique

## Result
SEEDING FAILED - NOT COMPLETED. Stopped immediately on error. Did not rerun blindly.

## Restoration
Override removed, config cleared, verified connection restored to archi.

## Notes
- archi untouched
- Partial seeding occurred (roles/permissions)
- No source files modified
