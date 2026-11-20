# Architecture Documentation (architecture.md)

**Project:** IGK School ERP — Laravel 12.70.0  
**SOP Reference:** §10 Architecture, §29 Handover Artefact #8  
**Owner:** D1  

---

## Approved Architecture

Plain Laravel — no Repositories, DTOs, Actions, Domain, or Modules directories. The project follows Laravel conventions with thin controllers, Form Requests for validation, and Policies for authorization.

---

## Folder Structure

```
app/
  Exceptions/           Custom exceptions (MarksLockedException, EnrollmentException)
  Http/
    Controllers/
      Admin/            24 controllers (role: admin)
      Auth/             4 controllers (login, password change, forgot, reset)
      Teacher/          5 controllers
      Student/          5 controllers
      Guardian/         3 controllers
      Report/           7 controllers
    Middleware/         EnsureRole, ForcePasswordChange
    Requests/           23 Form Requests
  Models/               23 Eloquent models
  Policies/             10 authorization policies
  Services/             7 services (see below)
  Support/              5 support classes (Money, AuditLogger, etc.)
database/
  migrations/           database schema migration set
  seeders/              6 seeders (incl. ProductionSeeder)
routes/
  web.php               Auth + role-scoped route groups
  modules/admin.php     Admin route resource definitions
  teacher.php, student.php, guardian.php, reports.php
resources/
  views/                Blade views (components, layouts, pdf)
tests/
  Feature/              21 feature test files
  Unit/                 Unit tests (GradingServiceTest, etc.)
```

---

## Core Business Services and Their Invariants

| Service | Responsibility | Key Invariants |
|---|---|---|
| `EnrollmentService` | Creates student + user, enforces class capacity, rejects duplicate admission numbers, drops/restores students | Capacity enforced; unique admission no. with campus prefix; drop removes enrollment record |
| `BranchTransferService` | Orchestrates student & teacher branch transfers, campus reassignments, and audit trails | Logs `from_branch_id`, `to_branch_id`, `transferred_by`, effective date, and status (`transferred`); maintains unorphaned teacher class assignments |
| `HrPayrollService` | Generates branch payroll runs, calculates Sri Lankan statutory items (EPF, ETF, APIT), executes dual-mode deductions | EPF 8%/12%, ETF 3%, progressive APIT; calculates automatic attendance deductions (unpaid leave + absent days) via pro-rata or fixed daily penalty; applies pending Super Admin manual deductions |
| `AttendanceSessionService` | Creates attendance sessions and records, enforces 48h edit window | Records editable only within 48h; clash detection for timetable |
| `MarksEntryService` | Saves/upserts marks, rejects when published, marks absent | Marks locked after publish; absent flag supported |
| `GradingService` | Calculates grades per paper, term GPA | Grade scale boundaries; absent = 0; null marks excluded from GPA; no results = null GPA |
| `ResultPublicationService` | Publishes/unpublishes results, locks marks | Only authorized can publish; locking enforced |
| `InvoiceService` | Generates invoices (idempotent, sequential numbering) | Unique sequential invoice numbers; idempotent generation |
| `PaymentService` | Records payments, receipts, reversals | Overpayment rejected (422); balance restored on reversal; no double reversal; receipt numbers gapless and unique |

---

## Multi-Branch Campus Architecture

1. **Campus Scoping & Isolation**:
   - Primary entities (`ClassModel`, `Section`, `Admission`, `PayrollRun`) use the `BranchOwned` global scope to enforce strict tenant isolation per physical campus.
   - Campus-wide roles use the active campus context; Accountant finance, journal, procurement, payroll, and deduction views must honor that selection.
   - Payroll run visibility for Accountant is determined by the campus of its employees, since a run can contain entries from more than one campus or have no run-level campus assignment.
2. **Student & Faculty Campus Assignment**:
   - `students.branch_id` and `teachers.branch_id` map each individual to their home campus.
   - When transferring campuses, `BranchTransferService` updates the home branch and logs an immutable audit trail in `branch_transfers`.

---

## Dual-Mode Salary Deduction Architecture

1. **Automatic Attendance Deductions**:
   - Evaluates staff attendance records (`staff_attendance.status = 'absent'`) and approved unpaid leave requests (`LeaveRequest.type = 'unpaid'`).
   - Supported Modes (Governed by Super Admin in `settings` table):
     - `pro_rata`: `(base_salary / 30) * unpaid_days`
     - `fixed_amount`: `Setting::get('payroll.auto_deduction_fixed_amount', 2000.00) * unpaid_days`
   - Automatically generates itemized `PayrollDeduction` record (`type = 'leave_unpaid'`) detailing day count and formula basis.
2. **Manual Custom / Wished Amount Deductions**:
   - Super Admin can assign a custom deduction of any desired amount (`amount`) to any employee.
   - Categorized by type: `advance`, `loan`, `damage`, `disciplinary_fine`, `other`.
   - Stored with `pending` status until the monthly `HrPayrollService::generatePayroll()` execution absorbs it into the payroll run and transitions status to `applied`.
3. **Super Admin Exclusivity & Connected Transparency**:
   - Dedicated controller: `App\Http\Controllers\SuperAdmin\SalaryDeductionController`.
   - Management route: `/admin/salary-deductions` (Super Admin exclusive); Accountant has a separate read-only, campus-scoped deduction history.
   - Policy, creation, and deletion controls remain unavailable to Accountant.
   - Full visibility on `/teacher/profile`: Staff members can inspect itemized deductions (both attendance and manual) on their personal portal and payslips.

---

## UI/UX, Dark Mode & Print Architecture

1. **Dark Mode Immunity**:
   - The application employs a glassmorphic theme supporting `data-bs-theme="dark"`.
   - Payslips and formal reports are wrapped in scoped container classes (`.payslip-sheet`) with explicit `#0f172a` typography and white backgrounds to prevent white-text-on-white-box collisions.
2. **A4 Portrait Physical Print Engine**:
   - Scoped `@page { size: A4 portrait; margin: 10mm 12mm; }` media queries.
   - Automatically hides navigation headers, action buttons, sidebars, and flash toasts during printing.
   - Output renders clean, high-contrast, official printed slips.

---

## Event/Job Map

- Side effects via Events/Listeners
- Heavy operations via queued jobs (`queue:work --tries=3 --max-time=3600`)
- Queue connection: database
- Scheduler: `schedule:run` every minute via cron; `school:sync-lifecycle` runs daily at 00:05 and is also triggered once daily by local web requests under a cache lock

---

## Authorization Model

- Spatie Laravel Permission for roles and permissions
- 10 Policies for resource-level authorization
- Middleware: `EnsureRole`, `ForcePasswordChange`
- All state-changing routes have Form Requests
- Authorization enforced by policy/middleware, never by hiding UI

---

## Security Standards (§10, §19)

- No `Repositories`, `DTOs`, `Actions`, `Domain`, or `Modules` directories
- No `$guarded = []`
- No `$request->all()`
- No `{!! !!}` outside PDF templates
- No `DB::raw` with user input
- `env()` only in config files
- Telescope absent from production (`composer install --no-dev`)
- Money is `decimal(12,2)` with BCMath arithmetic
- Uploads to private disk behind policy-checked signed routes
- Sensitive operations (payment reversal, marks lock) protected by policies/roles
- National IDs masked in list views; no PII in logs or flash messages
- `.env` never committed

---

## "Why Not" Decisions

| Decision | Reasoning |
|---|---|
| No Repositories/DTOs/Actions/Domain/Modules | SOP §10 specifies plain Laravel; keeps codebase simple for interns |
| No Livewire | Blade + Tailwind + Alpine per SOP §30.5 |
| No public API in v1.0 | SOP §4.3; explicitly noted so absence is a decision, not an omission |
| No General Ledger/Chart of Accounts | SOP excludes double-entry accounting from MVP |
| No SMS/email gateway | Post-MVP; announcements cover communication need |
| Telescope dev-only | Must not be installed in production (`--dev`, `TELESCOPE_ENABLED=false`) |
