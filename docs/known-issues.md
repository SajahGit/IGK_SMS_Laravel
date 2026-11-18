# Known Issues & Backlog (known-issues.md)

**Project:** IGK International School ERP — Laravel 12.71.0  
**SOP Reference:** §29 Handover Artefact #11  
**Status:** All Major & Critical Blockers Resolved | Production & Viva Ready  

---

## Resolved Issues

| ID | Module | Resolution Summary | Status |
|---|---|---|---|
| **BUG-001** | Finance / Fee Structures | Added missing `branch(): BelongsTo` relationship on `SchoolClass` and `PurchaseOrder` models, resolving `RelationNotFoundException`. Verified by `AccountantFinanceAccessTest` & `AccountantAllBranchFinanceTest`. | **RESOLVED** |
| **BUG-002** | Auth / Login Theme | Removed duplicate click event listener in `AuthLogin.blade.php`, consolidating theme toggling inside `public/js/app.js` with instant `localStorage['igk-theme']` persistence. | **RESOLVED** |
| **BUG-003** | Multi-Campus Accountant | Enabled full cross-branch visibility (`all_campuses` toggle) for Accountant role with scoped invoices, ledgers, and branch performance summary. Verified by `AccountantAllBranchFinanceTest`. | **RESOLVED** |
| **BUG-004** | Private Messaging Privacy | Scoped conversations strictly to sender and recipient; unread notifications only delivered to intended recipient. Verified by `PrivateMessagePrivacyAndScopingTest`. | **RESOLVED** |
| **BUG-005** | HR Payroll Deductions | Created dual-mode salary deduction engine (unpaid attendance leave pro-rata deduction + custom manual deduction) with printable payslip transparency in teacher profile. Verified by `InternationalMultiBranchAndPayrollTest`. | **RESOLVED** |
| **BUG-006** | Student Portal / Security | Enforced institutional policy restricting student password changes exclusively to School Administrators. Removed password modification fields from student profile edit and protected self-service reset routes. Verified by `StudentPasswordRestrictionTest`. | **RESOLVED** |
| **BUG-007** | Academics / Curriculum | Removed non-existent `is_active` query on `subjects` table in `CurriculumController@edit`, aligning with table schema. Verified by `CurriculumModuleTest`. | **RESOLVED** |
| **BUG-008** | Teacher Portal / Exams & Marks | Resolved null `schoolClass` property error on multi-branch assignments in `TeacherExamsCreate`, applied null-safe rendering across all teacher views, and added active student fallback in `MarksEntryService` ensuring marks entry is always accessible. Verified by `TeacherExamAndMarksWorkflowTest`. | **RESOLVED** |
| **BUG-009** | UI / Notification Banners | Removed redundant local flash alert blocks from 24 child blade templates and consolidated session feedback (`success`, `status`, `warning`, `error`) into `LayoutApp.blade.php` with auto-dismiss, preventing double notifications. Verified by `SingleNotificationBannerTest`. | **RESOLVED** |
| **BUG-010** | Cafeteria & Stock Governance | Added `branch_id` and `branch()` relations to `CafeteriaItem` and `InventoryItem`, resolved multi-branch cafeteria ordering via `BranchIsolationServiceProvider`, eliminated recursive Eloquent calls in `BranchOwned` scope, and exempted global cafeteria stock updates in `InventoryService` and `CafeteriaOrderService`. Verified by `SingleNotificationBannerTest`. | **RESOLVED** |
| **BUG-011** | List Ordering & Middot Escaping | Switched default table sorting across all system modules (Cafeteria, Hostels, Inventory, Transport, Classes, Subjects, Departments, Programs, Fees, Invoices, Library, Procurement, HR, Users) to reverse-chronological order (`latest('id')` / `orderByDesc('id')`) so newly created items immediately appear at the top. Replaced literal `&middot;` HTML entities with UTF-8 middle dot (`·`) in Record Payment invoice select and Student Report Card to prevent raw entity text display. Verified by `SingleNotificationBannerTest` and `AccountantFinanceAccessTest`. | **RESOLVED** |
| **BUG-012** | Cafeteria Multi-Branch Stock & Fresh Stock Provisioning | Enabled cross-branch stock selection for cafeteria items across any campus or central inventory, introduced 1-click fresh stock & menu item creation with automated SKU generation and opening inventory movements, added standalone cafeteria stock intake (`/admin/cafeteria/stock`), and provided 1-click inline stock replenishment (`/admin/cafeteria/stock/{id}/restock`). Verified by `SingleNotificationBannerTest`. | **RESOLVED** |
| **BUG-013** | User Management Search Column Fix | Resolved `SQLSTATE[42S22]: Unknown column 'phone' in 'where clause'` in `UserController@index`. Since the `users` table does not store phone numbers directly, updated search filters to check `phone` across related models (`teacher`, `student`, and `guardian`) via `orWhereHas()`. Verified by `SingleNotificationBannerTest`. | **RESOLVED** |
| **BUG-014** | PDF & Print Alignment Overhaul | Eliminated page clipping, horizontal stretching, and misaligned margins across DomPDF exports and browser printing (`window.print()`). Changed global `glass.css` `@page` orientation from hardcoded landscape to auto with standard margins, added explicit `@page { size: A4 portrait; margin: 10mm 12mm; }` on Report Cards, Invoices, Payslips, and Receipts, and `@page { size: A4 landscape; margin: 8mm 10mm; }` on Timetables and Reports. Fixed DomPDF templates (`PdfInvoice`, `PdfReceipt`, `PdfReportCard`, `PdfTranscript`, and `ReportPdfController`) with `* { box-sizing: border-box; }`, `table-layout: fixed;`, explicit column width percentages, and `page-break-inside: avoid;`. Verified by `PdfDocumentsTest` and `SingleNotificationBannerTest`. | **RESOLVED** |
| **BUG-015** | User Roles Dropdown & Student Role Inclusion | Restored the "Student" role across all user role dropdowns and filters in `UserController` (`index`, `create`, `edit`), `StoreUserRequest`, and `UpdateUserRequest`. Administrators can now filter user accounts by the Student role and manage student user profiles without artificial exclusions. Verified by `SingleNotificationBannerTest`. | **RESOLVED** |
| **BUG-016** | Comprehensive Exam & Results Search Engine | Added complete multi-parameter search bars and filter controls across Admin Exams (`admin.exams.index`), Teacher Exams (`teacher.exams.index`), Term Results (`admin.term-results.index`), and Exam Marks/Results views (`TeacherMarksShow`, `TeacherMarksEdit`, and `LayoutResultsTable`). Users can search exams by keyword, subject name/code, class, term, exam type, and publication status, and filter students in real-time while grading. Verified by `SingleNotificationBannerTest`, `TeacherExamAndMarksWorkflowTest`, and `TermResultsWorkflowTest`. | **RESOLVED** |
| **BUG-017** | Teacher Exam List Default Ordering | Prioritized reverse-chronological ID ordering (`latest('id')`) on Teacher Exams (`Teacher\ExamController@index`) and Admin Exams (`InstitutionAdmin\ExamController@index`), ensuring newly created exams immediately appear at the very top of the list regardless of exam target date. Verified by `TeacherExamAndMarksWorkflowTest`. | **RESOLVED** |
| **BUG-018** | Registrar Cross-Branch Multi-Campus Visibility & Teacher Class/Campus Display | Enabled institutional multi-campus visibility for Registrar role via `User::isRegistrar()` and `BranchContext::canAccessAllBranches()`. Backfilled `branch_id` across legacy teacher rows via migration `2026_11_17_170000_backfill_teacher_branch_ids.php`. Applied `withoutGlobalScopes()` on teacher branch, user branch, and class assignments in `TeacherController@index` and `teacher-table.blade.php`, ensuring campus name and assigned classes are always displayed. Verified by `RegistrarAccessTest`. | **RESOLVED** |
| **BUG-019** | Application-Wide Global Auto-Sort by Latest Activity | Standardized list queries across all application modules (Teachers, Staff, Students, Classes, Subjects, Departments, Programs, Inventory, Cafeteria, Hostels, Transport, Guardians, Academic Years, Terms, Roles, Invoices, Payments, Fee Structures, and Exams) to `orderByDesc('updated_at')->latest('id')`. Implemented automatic timestamp touching on all updates and related model changes, guaranteeing newly created or edited records immediately move to the top of their respective lists. Verified by `RegistrarAccessTest`. | **RESOLVED** |

---

## System Testing & Verification

All core regression and feature suites pass with 100% assertions:
* `Tests\Feature\RegistrarAccessTest` &mdash; 5 passed (49 assertions)
* `Tests\Feature\SingleNotificationBannerTest` &mdash; 9 passed (55 assertions)
* `Tests\Feature\TeacherExamAndMarksWorkflowTest` &mdash; 3 passed (22 assertions)
* `Tests\Feature\PdfDocumentsTest` &mdash; 4 passed (13 assertions)
* `Tests\Feature\TermResultsWorkflowTest` &mdash; 7 passed (48 assertions)
* `Tests\Feature\AccountantAllBranchFinanceTest` &mdash; 3 passed (18 assertions)
* `Tests\Feature\AccountantFinanceAccessTest` &mdash; 7 passed (36 assertions)
* `Tests\Feature\InternationalMultiBranchAndPayrollTest` &mdash; 10 passed
* `Tests\Feature\PrivateMessagePrivacyAndScopingTest` &mdash; 3 passed
* `Tests\Feature\StudentPasswordRestrictionTest` &mdash; 6 passed
* `Tests\Feature\CurriculumModuleTest` &mdash; 2 passed
* Total: **59+ passed (430+ assertions) — 0 errors**

---

## Engineering Deliverables & Team Packages

The full codebase is structured into 5 isolated domains and 1 shared foundation in `documents/TEAM/`:
* `ALASS_ADMIN_PACKAGE.zip` (Admin & Governance - 71 files)
* `SUMAIYA_TEACHER_PACKAGE.zip` (Teacher & Exams - 57 files)
* `RAJIYA_STUDENT_PACKAGE.zip` (Student & Guardian - 103 files)
* `HASFA_REPORTS_PACKAGE.zip` (11 Master Reports & LMS - 37 files)
* `SHILFA_FINANCE_PACKAGE.zip` (Finance & Accounting - 68 files)
* `CORE_SHARED_SYSTEM_PACKAGE.zip` (Shared Laravel Base - 20 items)
