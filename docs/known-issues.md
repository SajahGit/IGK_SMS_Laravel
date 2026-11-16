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

---

## System Testing & Verification

All core regression and feature suites pass with 100% assertions:
* `Tests\Feature\AccountantAllBranchFinanceTest` &mdash; 3 passed
* `Tests\Feature\AccountantFinanceAccessTest` &mdash; 6 passed
* `Tests\Feature\InternationalMultiBranchAndPayrollTest` &mdash; 10 passed
* `Tests\Feature\PrivateMessagePrivacyAndScopingTest` &mdash; 3 passed
* Total: **22 passed (169 assertions) — 0 errors**

---

## Engineering Deliverables & Team Packages

The full codebase is structured into 5 isolated domains and 1 shared foundation in `documents/TEAM/`:
* `ALASS_ADMIN_PACKAGE.zip` (Admin & Governance - 71 files)
* `SUMAIYA_TEACHER_PACKAGE.zip` (Teacher & Exams - 57 files)
* `RAJIYA_STUDENT_PACKAGE.zip` (Student & Guardian - 103 files)
* `HASFA_REPORTS_PACKAGE.zip` (11 Master Reports & LMS - 37 files)
* `SHILFA_FINANCE_PACKAGE.zip` (Finance & Accounting - 68 files)
* `CORE_SHARED_SYSTEM_PACKAGE.zip` (Shared Laravel Base - 20 items)
