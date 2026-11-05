# IGK SCHOOL ERP - SEEDER RECOVERY PLAN

**Generated:** 2026-11-02  
**Target:** archi_manual_test (partial - roles/permissions exist)  
**Protected:** archi (untouched)

## Files Requiring Changes
- database/seeders/DatabaseSeeder.php - use firstOrCreate/updateOrCreate for AcademicYear, Term, and other creates
- database/seeders/GradeScaleSeeder.php - use firstOrCreate/updateOrCreate for GradeScale/GradeScaleItem
- Potentially others with create() calls

## Root Cause
AcademicYear::create() fails on duplicate due to unique constraint. Need idempotent methods.

## Completed vs Remaining
Completed: RolePermissionSeeder. Remaining: SettingSeeder, GradeScaleSeeder, SuperAdminSeeder, rest of DatabaseSeeder.

## Safe Sequence (after fixes)
1. Verify target: ='archi_manual_test'; php artisan config:clear; php artisan config:show database
2. Run idempotent seeders individually or db:seed
3. Verify records
4. Restore: Remove-Item Env:DB_DATABASE; php artisan config:clear

## Safeguards
Never touch archi, never modify .env, never use migrate:fresh/refresh/wipe, always verify target.

**Plan only - no implementation**
