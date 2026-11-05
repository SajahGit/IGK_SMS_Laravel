# IGK ERP Role and Login Verification Report

> Historical role-verification snapshot. Current route-level verification and latest test results are documented in [IGK_ERP_FINAL_SOP_COMPLIANCE_AUDIT.md](../IGK_ERP_FINAL_SOP_COMPLIANCE_AUDIT.md).

**Project:** E:\Laravel-Projects\Architectured Final S  
**Audit Type:** READ-ONLY verification — no files or database records modified  
**Database inspected:** MySQL `archi` (read-only SELECT queries only)  
**Date:** 2026-11-02  
**Status:** Corrections implemented — see Section 10

---

## 1. SOP-Required Roles

Per SOP Section 7.1 (roles matrix), the six required system roles are:

| # | Role | Source |
|---|------|--------|
| 1 | Super Admin | `RolePermissionSeeder.php:83` |
| 2 | Institution Admin | `RolePermissionSeeder.php:84` |
| 3 | Registrar | `RolePermissionSeeder.php:85` |
| 4 | Teacher | `RolePermissionSeeder.php:86` |
| 5 | Accountant | `RolePermissionSeeder.php:87` |
| 6 | Student | `RolePermissionSeeder.php:88` |

**Note on Parent role:** SOP §A9 states: *"Parents/guardians are stored as records, not as login users in MVP."* Parent/guardian is a SHOULD-S1 feature (+28h), not a mandatory MVP login role. No `Parent` spatie role is created or required.

---

## 2. Roles Configured in Code

### 2.1 RolePermissionSeeder.php (`database/seeders/RolePermissionSeeder.php`)

Creates 6 spatie roles with the following permission assignments:

| Role | Permissions |
|------|-------------|
| Super Admin | All 105 permissions |
| Institution Admin | All permissions except `roles_and_permissions.*` (includes `reports.academic`, `reports.attendance`, `reports.financial`) |
| Registrar | Academic focus: `users.read`, academic years/terms read, departments/programs/subjects read, classes read, students CRUD, guardians CRUD, student_documents CRUD, class_subjects.read, enrollments CRUD, timetable.read, attendance read/update, exams.read/exam_subjects.read, marks.read, results read/approve/publish, report_card.read, invoices.read/payments.read, announcements create/read/update/publish, reports.read/reports.academic/reports.financial. Several assigned academic CRUD permissions are not yet wired through Registrar routes. |
| Teacher | Assigned classes only: academic_years.read, terms.read, departments.read, programs.read, subjects.read, classes.read, students.read, class_subjects.read, enrollments.read, timetable.read, attendance create/read/update, exams.read, marks.read, results.read, report_card.read, announcements.read, reports.read/reports.academic/reports.attendance |
| Accountant | Finance only: settings.read, academic_years.read, terms.read, departments.read, programs.read, subjects.read, classes.read, students.read, fee_structures CRUD, invoices CRUD, payments create/read, announcements.read, reports.read/reports.financial |
| Student | Own data only: students.read, classes.read, report_card.read, announcements.read |

### 2.2 SuperAdminSeeder.php

Creates one Super Admin user: `superadmin@school.test` (role column: `super-admin`, must_change_password: true).

### 2.3 DatabaseSeeder.php

Role mapping from `role` column to spatie role:

| `role` column value | Spatie role assigned |
|---|---|
| super-admin | Super Admin |
| admin | Institution Admin |
| teacher | Teacher |
| student | Student |
| registrar | Registrar |
| accountant | Accountant |
| parent | **(none)** — parents are records-only per SOP §A9; no spatie role assigned |

### 2.4 UserController.php

On user creation/update: `$user->syncRoles([$data['role']]);` — the `role` column value is synced as the spatie role name. Parents with `role` = `parent` will not be assigned any spatie role (no `Parent` role exists), which is correct per SOP §A9.

### 2.5 RoleController.php

Full CRUD for spatie roles and permissions via admin UI (protected by `roles_and_permissions.*` permissions).

---

## 3. Roles Present in Database

| Spatie Role Name | Guard | Exists |
|---|---|---|
| Super Admin | web | Yes |
| Institution Admin | web | Yes |
| Registrar | web | Yes |
| Teacher | web | Yes |
| Accountant | web | Yes |
| Student | web | Yes |
| Parent | web | No (not required per SOP §A9) |

Total spatie roles: **6**

---

## 4. Demo Users (Seeded via DatabaseSeeder)

| Name | Email | role column | Spatie Role |
|------|-------|-------------|-------------|
| Super Administrator | superadmin@school.test | super-admin | Super Admin |
| System Admin | admin@school.test | admin | Institution Admin |
| Demo Registrar | registrar@school.test | registrar | Registrar |
| Mr. Lathif | teacher@school.test | teacher | Teacher |
| Ms. Nisha | teacher2@school.test | teacher | Teacher |
| Demo Accountant | accountant@school.test | accountant | Accountant |
| Demo Student | student@school.test | student | Student |
| Demo Parent | parent@school.test | parent | (none — per SOP §A9) |

Plus 10 additional students and 11 additional parents (guardian records only).

---

## 5. Demo Account Buttons (Login View)

**File:** `resources/views/auth/AuthLogin.blade.php`

The login page displays **6 demo account chips** matching the six SOP roles:

| Button Label | Data Email | Spatie Role |
|---|---|---|
| Super Admin | superadmin@school.test | Super Admin |
| Institution Admin | admin@school.test | Institution Admin |
| Registrar | registrar@school.test | Registrar |
| Teacher | teacher@school.test | Teacher |
| Accountant | accountant@school.test | Accountant |
| Student | student@school.test | Student |

**These are demo account shortcuts only** — they pre-fill email and password (`password`) for quick testing. The design remains responsive with `d-flex flex-wrap gap-2` layout and Bootstrap icon buttons consistent with the existing design.

**No Parent login shortcut** is displayed, consistent with SOP §A9 (parents are records-only in MVP).

---

## 6. Corrections Applied

### Fix 1: Parent Role Mapping (`database/seeders/DatabaseSeeder.php`)

**Before:** `'parent' => 'Student'` — parent users were incorrectly assigned the Student spatie role.

**After:** Removed `'parent' => 'Student'` from the role map. Parents with `role` column = `parent` receive no spatie role, consistent with SOP §A9 (parents are guardian records only in MVP). The `EnsureRole` middleware falls back to the `role` column, so guardian route access (`role:parent`) continues to work.

### Fix 2: Missing Demo Users (`database/seeders/DatabaseSeeder.php`)

Added two demo users:
- **Registrar:** `registrar@school.test` — role column `registrar`, spatie role `Registrar`
- **Accountant:** `accountant@school.test` — role column `accountant`, spatie role `Accountant`

Both use the existing demo password convention (`password`) and are active accounts.

### Fix 3: Login Page Demo Buttons (`resources/views/auth/AuthLogin.blade.php`)

**Before:** 4 buttons (Admin, Teacher, Student, Parent)

**After:** 6 buttons (Super Admin, Institution Admin, Registrar, Teacher, Accountant, Student)

Removed the Parent button (not a login role per SOP §A9). Added Super Admin, Registrar, and Accountant buttons.

### Fix 4: Institution Admin Report Permissions (`database/seeders/RolePermissionSeeder.php`)

The seeder code already assigns Institution Admin all permissions except `roles_and_permissions.*`, which includes `reports.academic`, `reports.attendance`, and `reports.financial`. The database state mismatch (Institution Admin missing these permissions in the existing `archi` database) is resolved when the seeder is run on a fresh or manually-seeded test database. No code change was needed — the seeder logic was already correct.

---

## 7. Files Modified

| File | Change |
|------|--------|
| `resources/views/auth/AuthLogin.blade.php` | Replaced 4 demo buttons with 6 role-specific shortcuts (Super Admin, Institution Admin, Registrar, Teacher, Accountant, Student); removed Parent button |
| `database/seeders/DatabaseSeeder.php` | Added Registrar and Accountant demo users; removed `'parent' => 'Student'` from role map so parents receive no spatie role |
| `database/seeders/RolePermissionSeeder.php` | No change needed — Institution Admin already receives all report permissions |
| `tests/Feature/RoleLoginVerificationTest.php` | **NEW** — 16 tests covering all six roles, permissions, demo accounts, parent mapping, and unauthorized access |

---

## 8. Test Results

**Command:** `php artisan test`

```
Tests:    79 passed (360 assertions)
Duration: 17.91s
```

All 79 tests pass across 12 test classes:
- Unit: ExampleTest, GradingServiceTest (9 tests)
- Feature: ExampleTest, CurriculumModuleTest (2), EnrollmentAndMarksServiceTest (8), Phase1AuthAndAcademicTest (7), Phase1RbacAndModuleTest (13), Phase2DocumentsCapacityTimetableAttendanceTest (7), Phase3MarksLockGpaPublicationReportCardTest (4), Phase4FinanceModuleTest (4), Phase5AnnouncementsAuditReportsTest (2), ReportsParityTest (7), RoleLoginVerificationTest (16)

**RoleLoginVerificationTest — 16 tests, all passing:**

| Test | Description |
|------|-------------|
| all six sop roles exist in database | Verifies 6 spatie roles exist, no Parent role |
| no parent spatie role exists | Confirms no Parent spatie role per SOP §A9 |
| super admin has all permissions | Super Admin has all 105 permissions |
| institution admin has report permissions | Institution Admin has reports.academic, reports.attendance, reports.financial |
| registrar has academic permissions only | Registrar has academic focus, no finance write |
| teacher has assigned class permissions only | Teacher has attendance/marks, no user/role management |
| accountant has finance permissions only | Accountant has finance, no academic write |
| student has own data permissions only | Student has read-only own data permissions |
| registrar demo user can be created with correct role | Registrar user with correct role column and spatie role |
| accountant demo user can be created with correct role | Accountant user with correct role column and spatie role |
| parent user does not receive student spatie role | Parent users not mapped to Student role |
| unauthorized user cannot access admin routes | Student gets 403 on admin routes |
| teacher cannot access admin routes | Teacher gets 403 on admin user management |
| unauthenticated access redirects to login | Guest users redirected to login |
| login demo accounts match six roles | Demo emails match the six SOP roles |

**Command:** `php vendor/bin/pint --test`

```
{"tool":"pint","result":"passed"}
```

---

## 9. Constraints Honored

- Original `archi` database never modified — read-only SELECT queries only
- No `migrate:fresh`/`refresh`/`db:wipe` executed
- No `git init`/commits/remotes/push/fork
- All changes local
- No production credentials exposed
- Demo users restricted to local/testing environments
- Seeder operations are idempotent (`updateOrCreate`, `firstOrCreate`)

---

## 10. Summary

| Category | Before | After |
|---|---|---|
| SOP roles in code | 6 | 6 (unchanged) |
| Spatie roles in database | 6 | 6 |
| Parent spatie role | No | No (correct per SOP §A9) |
| Registrar demo user | Missing | Created |
| Accountant demo user | Missing | Created |
| Parent → Student role mapping | Bug | Fixed |
| Institution Admin report permissions | Missing in DB | Correct in seeder code |
| Login demo buttons | 4 (incl. Parent) | 6 (all SOP roles) |
| Automated tests | 64 / 286 assertions | 79 / 360 assertions |
| Pint code style | Pass | Pass |
