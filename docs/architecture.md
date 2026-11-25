# Architecture Documentation (architecture.md)

**Project:** IGK School ERP — Laravel 12.71.0  
**SOP Reference:** §10 Architecture, §29 Handover Artefact #8  
**Scope:** 25 Core Functional Modules (35 Sub-systems) • 71 Business Logic Engines • 8 Role Portals • Multi-Campus Branch Isolation  
**Owner:** Core Architecture & Engineering Team  

---

## Approved Architecture

Plain Laravel — thin controllers, Form Requests for validation, dedicated Domain Services for multi-step transactional mutations, and Policies for authorization. Features strict multi-campus data isolation via `BranchOwned` global query scoping.

---

## Folder Structure

```
app/
  Enums/                Domain enums (AttendanceStatus, InvoiceStatus, ExamStatus, etc.)
  Events/               Domain events (AnnouncementPublished, InvoiceIssued, PaymentRecorded, ResultPublished)
  Exceptions/           Domain exceptions (MarksLockedException, EnrollmentException, PaymentExceedsBalanceException)
  Http/
    Controllers/
      InstitutionAdmin/ 27 controllers (users, branches, academics, finance, HR, transport, hostel, cafeteria, inventory)
      SuperAdmin/       2 controllers (roles, salary deduction policies)
      Auth/             5 controllers (login, password change, forgot, reset, two-factor TOTP)
      Teacher/          7 controllers (classes, attendance, exams, marks, timetable, LMS, profile)
      Accountant/       4 controllers (fee structures, invoices, payments, dashboard)
      Registrar/        1 controller (registrar dashboard & student enrollment)
      Student/          9 controllers (dashboard, timetable, electives, attendance, results, invoices, ledger, cafeteria, hostel, LMS)
      Guardian/         5 controllers (dashboard, child attendance, results, report card, invoices)
      Library/          2 controllers (circulation desk, catalog master)
      Procurement/      2 controllers (suppliers, purchase orders)
      Report/           11 controllers (11 master reports suite + DomPDF streamer)
    Middleware/         EnsureRole, EnsurePasswordIsChanged, SetActiveBranchContext
    Requests/           Form request validation classes
  Jobs/                 7 asynchronous queue jobs (GenerateMonthlyPayroll, GenerateTermInvoices, GenerateTermResults, etc.)
  Listeners/            Domain event listeners (SendInvoiceIssuedNotification, SendPaymentReceivedNotification)
  Mail/                 Mailable classes (DailyAbsenteeDigest)
  Models/               Eloquent models with relational constraints & BranchOwned scopes
  Notifications/        Multi-channel notifications (Database, Mail)
  Observers/            AuditObserver (Actor-backed mutation auditing with password/token masking)
  Policies/             13 authorization policies (UserPolicy, RolePolicy, MarkPolicy, etc.)
  Rules/                Domain validation rules (UniqueStudentDocumentNumber, ValidInternationalPhone, etc.)
  Services/             26 business logic services (Enrollment, Transfer, Payroll, Invoicing, Grading, etc.)
  Support/              15 domain & calculation engines (Money, NumberSequence, TimetableClashDetector, etc.)
database/
  migrations/           66 database schema migrations (55+ relational tables)
  seeders/              Dedicated demo & production seeders
routes/
  web.php               Auth, account, notifications, 1-on-1 private messages, branch-context
  modules/admin.php     Institution administration endpoints
  accounting.php        Chart of accounts, journals, trial balance, ledger
  teacher.php, student.php, guardian.php, library.php, procurement.php, reports.php
resources/
  views/                Blade views with glassmorphic dark theme, print-optimized sheets, and zero-clipping table actions
tests/
  Feature/              49 feature test files (100% passed)
  Unit/                 Unit calculation tests
```

---

## Core Business Services & Domain Logics (71 Engines Across 7 Domains)

The system enforces 71 dedicated business logics across 7 enterprise domains (see `docs/IGK_ERP_MODULES_AND_LOGICS_MASTER_REFERENCE.pdf` for the complete zero-loss specification):

| Domain | Services & Support Classes | Key Invariants & Rules |
|---|---|---|
| **1. Academic Lifecycle** | `EnrollmentService`, `AcademicLifecycleService`, `BranchTransferService`, `ElectiveRegistrationService`, `AdmissionNumberGenerator`, `TimetableClashDetector` | Collision-free admission numbering; classroom quota gating; auto-enrollment in core subjects; multi-level student promotion; 3D timetable clash prevention (day/period/teacher/room/class). |
| **2. Attendance & Exams** | `AttendanceSessionService`, `MarksEntryService`, `GradeScaleService`, `GradingService`, `ResultPublicationService`, `TermResultService`, `TranscriptService` | 48-hour tamper-proof attendance edit lock; duplicate daily session prevention; contiguous 0–100% grade scale; immutable exam lock on publication; credit-weighted GPA/CGPA; 4-stage term results state machine. |
| **3. Finance & Accounting** | `InvoiceService`, `PaymentService`, `JournalService`, `PayHereService`, `Money`, `NumberSequence`, `NumberToWords` | Pessimistic row-locking gapless sequential numbering (`INV`, `REC`); BCMath arbitrary-precision zero-rounding currency; overpayment rejection; Super Admin payment reversals; double-entry balancing ($\sum \text{Debits} == \sum \text{Credits}$); PayHere HMAC-SHA256 signature verification. |
| **4. HR & Statutory Payroll** | `HrPayrollService`, `SriLankaPayrollCalculator`, `SuperAdmin\SalaryDeductionController`, `BranchTransferService` | Sri Lanka statutory compliance: EPF (8% employee, 12% employer), ETF (3% employer), progressive APIT withholding tax; dual-mode attendance deductions (dynamic pro-rata vs fixed daily); Super Admin custom deduction governance; 4-eye segregation of duties approval; automatic bank GL posting. |
| **5. Campus Operations** | `HostelAssignmentService`, `CafeteriaOrderService`, `InventoryService`, `ProcurementService`, `TransportService` | Hostel single-bed allocation invariant; cafeteria pessimistic item stock locking (`lockForUpdate()`); order cancellation stock restoration; warehouse non-negative stock balance; purchase order 5-stage state machine (Draft ➔ Submitted ➔ Approved ➔ Received ➔ Cancelled); vehicle licensed seat quota enforcement. |
| **6. Multi-Branch Security** | `BranchOwned`, `BranchContext`, `AuditObserver`, `TwoFactorController`, `EnsurePasswordIsChanged`, `InternationalPhoneNumber` | `BranchOwned` global query scoping; active branch session resolution; actor-backed mutation audit logs with sensitive credential masking; Spatie two-tier RBAC; RFC 6238 TOTP 2FA with configurable `.env` toggle (`TWO_FACTOR_ENABLED`); forced password change on first login; E.164 phone normalizer. |
| **7. Background Automation** | `GenerateMonthlyPayroll`, `GenerateTermInvoices`, `GenerateTermResults`, `SendOverdueInvoiceReminders`, `NotifyOverdueLibraryLoans`, `NotifyLowStockItems`, `BulkCsvImportService`, `DailyAbsenteeDigest` | Scheduled end-of-month payroll generation; chunked memory-capped bulk invoicing; async term results compiler; overdue invoice escalation mailer; automated low-stock alerts; line-by-line CSV streaming up to 5,000 rows; daily registrar absentee digest; after-commit notification dispatchers. |

---

## Mandatory Two-Factor Authentication (2FA) & Administrator Forgot Password OTP

The platform enforces strict administrative multi-factor security:
1. **Mandatory Administrative 2FA**: Super Admin and Institution Admin accounts must authenticate with RFC 6238 TOTP via Google Authenticator on every login without exception.
2. **Visual QR Code & Secret Key Pairing**: During onboarding or after reset, `/two-factor/setup` renders an inline SVG QR Code generated via `BaconQrCode` (`ImageRenderer` with `SvgImageBackEnd`) alongside a 16-character manual setup key with instant clipboard copying.
3. **No Remember Device Bypass**: Admin sessions disable long-term remember cookies, ensuring fresh TOTP validation upon each login, session expiration, and browser restart.
4. **Emergency Reset & Recovery**: Administrators receive 12 one-time emergency recovery codes. Administrators can also be reset using `php scratch/execute_reset_2fa.php` for fresh client handover.
5. **Administrator Forgot Password (6-Digit Email OTP + Gmail SMTP)**:
   - Implemented via `PasswordResetOtp` (`password_reset_otps` table), `PasswordResetOtpService`, `PasswordResetOtpMail`, `PasswordChangedConfirmationMail`, `ForgotPasswordController`, and `ResetPasswordController`.
   - Strictly restricted to active **Super Admin** and **Institution Admin** accounts (Students, Teachers, and other Staff remain admin-managed).
   - Uses cryptographically secure 6-digit OTPs (Bcrypt-hashed, 5-min expiry, max 5 attempts, 60s resend cooldown, isolated named rate limiters in `AppServiceProvider`), single-use SHA-256 reset tokens, session/token revocation, and preserves existing Google Authenticator 2FA configuration.
   - `Password::defaults()` runs `uncompromised()` in `production` and enforces `min(8)->max(32)->mixedCase()->numbers()` in local/testing environments to avoid Windows XAMPP cURL CA certificate errors (`cURL error 60`).

## Staff & Teacher Personal Attendance Architecture

1. **Relational Architecture**: The `staff_attendance` table stores both `teacher_id` and `employee_id` (foreign key to `employees`), ensuring complete parity between academic teachers and operational staff (Accountants, Registrars, Librarians, Admin Staff).
2. **Personal Self-Service View**:
   - Teachers access their personal daily attendance log, check-in, check-out, working hours, and status badges via `/teacher/profile` (Tab: "Leaves & Attendance History").
   - Non-teacher employees access their personal attendance history via `/my-attendance` and the topbar attendance shortcut.
3. **Automated Status & Performance Metrics**:
   - `StaffAttendance` model accessors compute formatted times (`check_in_formatted`, `check_out_formatted`), duration (`working_hours_formatted`), and status (`present`, `late`, `half_day`, `absent`).
   - Summary statistics calculate Monthly Days Present, Total Accumulated Hours, Average Shift Duration, and Punctuality / On-Time Rate.

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
3. **Shared Dropdown Positioning**:
   - `public/js/app.js` configures Bootstrap menus for `bottom-start` absolute placement and disables Popper flipping. Searchable selects use static placement anchored to their wrapper.
   - `public/css/glass.css` constrains searchable, issuing-country, nationality, and phone-country menus to their component widths and gives long option lists internal scrolling.
   - The shared regression is documented in [Global Dropdown UI](./guides/global-dropdown-ui.md). The component matrix passed; authenticated page-by-page role coverage remains incomplete.

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
