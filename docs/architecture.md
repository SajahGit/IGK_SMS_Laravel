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

## The 7 Services and Their Invariants

| Service | Responsibility | Key Invariants |
|---|---|---|
| `EnrollmentService` | Creates student + user, enforces class capacity, rejects duplicate admission numbers, drops/restores students | Capacity enforced; unique admission no.; drop removes enrollment record |
| `AttendanceSessionService` | Creates attendance sessions and records, enforces 48h edit window | Records editable only within 48h; clash detection for timetable |
| `MarksEntryService` | Saves/upserts marks, rejects when published, marks absent | Marks locked after publish; absent flag supported |
| `GradingService` | Calculates grades per paper, term GPA | Grade scale boundaries; absent = 0; null marks excluded from GPA; no results = null GPA |
| `ResultPublicationService` | Publishes/unpublishes results, locks marks | Only authorized can publish; locking enforced |
| `InvoiceService` | Generates invoices (idempotent, sequential numbering) | Unique sequential invoice numbers; idempotent generation |
| `PaymentService` | Records payments, receipts, reversals | Overpayment rejected (422); balance restored on reversal; no double reversal; receipt numbers gapless and unique |

---

## Event/Job Map

- Side effects via Events/Listeners
- Heavy operations via queued jobs (`queue:work --tries=3 --max-time=3600`)
- Queue connection: database
- Scheduler: `schedule:run` every minute via cron

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
