# IGK SCHOOL ERP - SEEDING FAILURE INVESTIGATION REPORT

**Generated:** 2026-11-02  
**Target:** archi_manual_test | **Protected:** archi (untouched)

## Root Cause
Unique constraint violation on academic_years.name='2026'. DatabaseSeeder uses AcademicYear::create() without checking existence - not idempotent. Partial seeding from previous attempt may have created the record, or create() failed after starting transaction.

## Partial Seeding
RolePermissionSeeder completed successfully (roles/permissions created). DatabaseSeeder failed at AcademicYear creation. No term/classes/students/finance data from DatabaseSeeder inserted.

## Seeder Safety
- RolePermissionSeeder/SuperAdminSeeder/SettingSeeder use firstOrCreate/updateOrCreate - safer
- DatabaseSeeder and GradeScaleSeeder use create() - not idempotent, will fail on duplicates

## Recommendations (no changes made)
DatabaseSeeder needs to use firstOrCreate/updateOrCreate for AcademicYear, Term, and other creates to be idempotent. Cannot modify seeders per constraints. archi_manual_test is in partial state with roles/permissions; to proceed safely would need approval to fix seeders or reset DB (not allowed).

**Investigation Complete - Read-only, no modifications**
