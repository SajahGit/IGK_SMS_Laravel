# IGK School ERP QA Workspace

This directory contains the Stage 3 QA architecture, Stage 4 evidence, and partial browser results. It is not a full application sign-off.

## Current status — 2026-11-21

The shared dropdown component regression passed 10 Chromium cases across five viewport projects in both themes. Six shared menu variants were checked per case. Authenticated page-by-page dropdown verification for the eight roles remains incomplete; the test database has no users or role/permission data. See the [dropdown result register](Results/DROPDOWN_UI_RESULTS.csv), [evidence index](Evidence/EVIDENCE_INDEX.csv), and [Stage 4 report](Final-Reports/STAGE4_BROWSER_TESTING_REPORT.md).

## Database rule

Browser and QA CRUD execution must use `school_management_system_testing_new_db` only. The original `.env` database must never be used for QA writes. Database creation or migrations require separate approval.

## Role scope

The project implements eight QA roles:

1. Super Admin
2. Institution Admin
3. Registrar
4. Teacher
5. Accountant
6. Student
7. Guardian/Parent
8. Librarian

Librarian is tested as the eighth role but is not a login-page role option. Library access is covered through Super Admin and Institution Admin permissions.
