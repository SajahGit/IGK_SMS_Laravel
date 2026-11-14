# IGK School ERP — Complete System, Code Map, and Presentation Guide

> **Use:** A handover and presentation source for teammates, reviewers, and AI slide/report tools. This document describes the working tree at the time it was prepared. Paths are relative to the repository root; generated dependencies and private local configuration are intentionally excluded.
>
> **Important:** This is a source-code orientation guide, not a guarantee that every listed feature is production-ready. Confirm behavior against the code, tests, and current issue list before presenting a feature as complete.

## 1. Project at a glance

IGK School ERP is a Laravel web application for school administration. It groups users by role and provides academic, student, guardian, teacher, finance, library, operations, and reporting workflows. The browser requests are handled by Laravel routes and middleware, validated by request classes, processed by controllers/services/models, persisted in the configured database, and rendered through Blade views or returned as files/API responses.

**தமிழில் சுருக்கம்:** IGK School ERP என்பது பள்ளியின் மாணவர், பெற்றோர்/guardian, ஆசிரியர், கல்வி, கட்டணம், கணக்கியல், நூலகம் மற்றும் நிர்வாகப் பணிகளை role-based access உடன் ஒருங்கிணைக்கும் Laravel web system. User ஒரு form submit செய்தால் route/middleware access சரிபார்க்கும்; Request validation பின் Controller/Service business rules-ஐ இயக்கி database-ல் சேமிக்கும்; பின்னர் Blade page, report அல்லது PDF response காட்டப்படும். Demo/test data local database-ல் மட்டுமே பயன்படுத்த வேண்டும்.

### Technology and entry points

`composer.json` declares PHP `^8.2` and Laravel `^12`; the locked dependencies also include Sanctum, DomPDF, international phone-number support, Google 2FA, and Spatie permissions. The browser UI is mainly Blade with Tailwind/Vite source assets.

| Concern | Project location |
|---|---|
| Web entry point and Laravel bootstrap | `public/index.php`, `artisan`, `bootstrap/` |
| Web/API/console route declarations | `routes/` |
| Domain models and business services | `app/` |
| Database schema, demo data, and factories | `database/` |
| Browser UI | `resources/views/`, `resources/js/`, `resources/css/` |
| Automated tests | `tests/`, `phpunit.xml` |
| PHP/frontend dependencies and build | `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js` |
| Team/local setup | `README-SETUP.md`, `setup-team.bat`, `.env.example` |

### High-level request lifecycle

```text
Browser
  -> public/index.php
  -> bootstrap/app.php and service providers
  -> routes/web.php (and included route files)
  -> authentication / role / permission / branch middleware
  -> Form Request validation (where used)
  -> controller action
  -> service/model/database transaction and domain events (where used)
  -> Blade view, redirect, JSON, PDF, or download response
  -> browser
```

`artisan` is the command-line entry point for migrations, seeders, queues, scheduled commands, and tests. Environment-specific behavior is controlled by `.env`; `.env.example` is a non-secret local template. Each developer must use their own `.env` and database.

## 2. Audience-facing system summary

### Problem and users

The system brings common school records and work into one role-based application. Users see only the screens and actions permitted for their role and, where applicable, their institution/branch context.

The current demo roles are:

| Role | Typical responsibility |
|---|---|
| Super Admin | Platform-wide setup, roles, permissions, and privileged administration |
| Institution Admin | Institution and campus operations |
| Registrar | Student and academic administration |
| Teacher | Classes, learning, attendance, and academic work |
| Accountant | Fees, invoices, payments, receipts, and finance views |
| Librarian | Library catalog and circulation |
| Student | Own learning, profile, and permitted student portal views |
| Guardian | Linked student/family views and permitted communication |

Exact actions are determined by the current permission definitions and routes; this table is a stakeholder-level overview, not an authorization specification.

## 3. Complete repository folder and file map

This section is expanded from the current source tree. It explains what each project area owns and points to the detailed file/function catalog later in this document. It excludes installed `vendor/` and `node_modules/`, local secrets, generated build output, and runtime logs/caches because those are not application source.

| Folder/file | Purpose and runtime role |
|---|---|
| `app/` | Laravel application code: controllers, requests, middleware, models, services, policies, jobs, events, and supporting domain logic. |
| `app/Console/` | Application-specific Artisan commands and scheduled task behavior. |
| `app/Contracts/` | Interfaces/contracts used to describe dependencies between application components. |
| `app/Enums/` | Typed domain constants and state/role values. |
| `app/Events/`, `app/Listeners/` | Domain event declarations and follow-up actions. |
| `app/Exceptions/` | Application-specific exception handling/types. |
| `app/Http/` | Web/API controllers, request validation, and middleware. |
| `app/Jobs/` | Queueable or deferred work. |
| `app/Mail/`, `app/Notifications/` | Email and notification delivery classes. |
| `app/Models/` | Eloquent entities, relationships, casts, scopes, and model behavior. |
| `app/Observers/` | Model lifecycle hooks and cross-cutting record handling. |
| `app/Policies/` | Record/action authorization rules. |
| `app/Providers/` | Laravel service/container and application boot registration. |
| `app/Rules/` | Reusable validation rules. |
| `app/Services/`, `app/Support/` | Business workflows and reusable domain/infrastructure helpers. |
| `bin/` | Bundled Windows/CLI launcher scripts for development tools such as Pint and PHPUnit; not application business logic. |
| `bootstrap/` | Laravel application configuration and cached bootstrap state. |
| `config/` | Environment-backed settings for app, database, auth, mail, services, school behavior, and related integrations. |
| `database/factories/` | Test/factory definitions for creating model records. |
| `database/migrations/` | Versioned schema changes. |
| `database/seeders/` | Roles, permissions, baseline configuration, and local/test demo data. |
| `deploy/` | Deployment, service, web-server, backup, or scheduler support files. |
| `docs/` | Architecture, SOP, testing, setup, and historical project documentation. |
| `public/` | Web server document root and public static resources. |
| `resources/css/`, `resources/js/` | Frontend source assets compiled by Vite. |
| `resources/views/` | Blade templates/layouts/components for server-rendered pages. |
| `routes/` | Web, API, console, and role/module route declarations. |
| `storage/` | Runtime uploads, generated files, logs, sessions, and caches; not team source data. |
| `tests/Feature/` | End-to-end/request-level and module workflow tests. |
| `tests/Unit/` | Isolated unit-level behavior tests. |
| `tests/Concerns/` | Shared test helpers/traits. |
| `artisan` | CLI entry point. |
| `composer.json` / `composer.lock` | PHP dependencies, autoloading, and Composer scripts. |
| `package.json` / `package-lock.json` | Frontend dependencies and npm scripts/lockfile. |
| `phpunit.xml` | PHPUnit environment and test runner configuration. |
| `phpstan.neon` / `pint.json` | Static-analysis and formatting configuration. |
| `vite.config.js` | Frontend asset build configuration. |
| `setup-team.bat` | Windows setup helper; installs dependencies, initializes a missing local `.env`, and builds assets. |
| `.env.example` | Safe starter configuration; copy to ignored `.env` and generate a unique key locally. |
| `README.md` / `README-SETUP.md` | Project introduction and local/team setup procedure. |
| `IGK_ERP_TEAM_MODULE_OWNERSHIP_MATRIX.md` | Agreed team work allocation and shared-file coordination rules. |
| `sample dataset.md` | Demo login reference and role-wise manual QA examples. |
| `IGK_SCHOOL_ERP_MANUAL_*.md` | Manual-test environment, database safety, setup, functional checklist, and seeder recovery guides. |
| `sms-49day-laravel-sop.html` | Project SOP reference document. |
| `MANIFEST.txt` | Project file/hand-over manifest. |

### Excluded/generated folders

- `vendor/` and `node_modules/` are installed dependencies; recreate them with Composer/npm.
- `public/build/` is generated by the frontend build.
- Runtime files below `storage/` are environment data, not source-controlled demo fixtures.
- `.env` may contain local credentials and keys; never include it in Git, ZIP, a presentation, or a third-party AI prompt.

Working-tree inventory counts (generated/dependency directories excluded where applicable): `app/` 327 files; `routes/` 14; `database/` 66 migrations, 21 seeders and 16 factories; `resources/` 195 Blade views plus 3 source CSS/JS files; `config/` 15; `bootstrap/` 2; `tests/` 44 test/support files; `docs/` 30; `deploy/` 5. Counts describe the inspected tree and should be refreshed after structural changes.

## 4. Runtime and data flows

This section describes the common flow patterns. Module-specific route/controller/service details are in the file catalog.

### 4.1 Browser page / form submission

1. A route maps an HTTP method and URL to a controller action (or a route closure).
2. Middleware checks concerns such as login, role/permission, two-factor requirements, and branch scope.
3. A Form Request or controller validates input.
4. The controller reads/writes Eloquent models and delegates multi-step work to services where available.
5. Database constraints and transactions protect persistent state; events/jobs may run follow-up work.
6. The controller returns a Blade view, redirect with status/errors, JSON, PDF, or a file response.

### 4.2 Local installation and demo data

```text
Copy .env.example to a developer-owned .env
  -> generate unique APP_KEY
  -> create that developer's local MySQL database
       school_management_system_testing_db
  -> create schema with migrations
  -> DatabaseSeeder selects environment-safe seeding path
  -> local/test seeders create repeatable demo users and records
  -> php artisan serve
  -> sign in with a documented local demo role
```

The full install and destructive-reset warnings are in `README-SETUP.md`; demo users and role examples are in `sample dataset.md`. Tests use the database settings in `phpunit.xml`. Production seeding is intentionally separate from local demo seeding.

The shared DB name is intended for separate local MySQL instances per teammate, not for simultaneous connections to one schema. A `migrate --seed` run creates rows only in the database currently selected by `.env`; it does not copy/replace another schema's records. The documentation edits do not themselves insert sample data.

**Workspace configuration snapshot checked 2026-11-14:** the existing `.env` was set to local MySQL `127.0.0.1:3306/school_management_new` (10,017 `users` rows; one Super Admin, 239 `demo.student.*` users, six of the documented standard role logins; 64 applied migrations and two pending). The requested `school_management_system_testing_db` schema was not present then. The DB already contained demo identifiers; the documentation edits did not insert those records. Treat this as a dated snapshot, not an instruction to switch/reset or delete the current database; verify the active target with `php artisan config:show database.connections.mysql.database` before database commands. Full details are in `README-SETUP.md`.

### 4.3 Typical student-to-finance/report journey

```text
Institution/academic structure
  -> student and guardian records
  -> enrollment into class/academic year/term
  -> teacher attendance and assessment/results
  -> fee structure/items
  -> invoice
  -> payment and receipt
  -> reports/dashboard/PDF
```

Exact prerequisites and implemented transaction boundaries vary by workflow; see the service/controller catalog and tests before explaining any edge case as guaranteed.

### 4.4 Authorization and branch scope

Authentication establishes the signed-in user. Role/permission middleware and policies decide whether an action is allowed. Branch-context logic scopes relevant operations to the active institution/branch. Controllers should validate related record access as well as input shape. The exact enforcement points and tests are listed in the HTTP/domain catalog.

### 4.5 Verified business-workflow patterns

These are implementation patterns found in the services/controllers. They are useful for a live demo because they show both the user action and the data rule behind it.

**Admission and enrollment:** An authorized administrator submits student, academic placement, and guardian data → request rules normalize and validate it → `EnrollmentService` checks admission number, year/program and class capacity → a transaction creates/links the account and student profile, synchronizes guardians and required class subjects, then records audit information → the UI returns the saved student and relationships. Dropping an enrolment also handles the local student documents and linked account/profile.

**Attendance and results:** A teacher selects their eligible class/subject → attendance-session service finds active enrolments and creates one session per unique class/subject/date/time → attendance/marks are validated against that enrollment and teacher assignment → marks are bulk-upserted transactionally → exam publication checks completeness and locks marks → term-result generation aggregates published exams → submit/publish transitions lock recalculation and publish notifications to students/guardians.

**Fees, invoices, and payment:** An accountant configures applicable fee structures/items → invoice generation selects active applicable fees for eligible students, validates currency/discount, and avoids duplicate generation → issue moves draft invoices to live/issued state → payment records lock the invoice, reject invalid/over-balance payment, allocate a receipt number, update balance/status and audit → reversal preserves the original payment, adds reversal metadata and recalculates the balance → journal synchronization separately mirrors issued invoices/unreversed payments to accounting entries. Payment/invoice notifications are dispatched after commit.

**Purchasing and stock:** A user creates a supplier-linked purchase order → submits → an authorized approver approves → full or partial receipt validates quantities/campus and creates inventory receipt movements within a transaction → stock balance changes and audit trail are recorded. Inventory adjustment/issue likewise checks the resulting balance; deleting referenced stock records is guarded.

**Payroll:** HR maintains employees and leave requests → a monthly payroll command/job creates a draft (not paid) run → payroll entries calculate configured statutory values → a different authorized person approves → marking paid posts a balanced journal. Generation does not by itself approve or pay staff.

**Portal and communications:** Students and guardians see their permitted linked records; teachers act only on assigned classes/subjects; announcements select recipients by role/academic relationship; messages and notifications are stored for the receiving account. Files/transcripts/reports are returned only through their authorized routes.

## 5. Team module ownership and presentation mapping

This is the team's agreed allocation from the ownership matrix; it does not assert every item is absent or fully implemented. Before a new change, inspect the existing route/controller/model/test and implement only the agreed gap.

| Member | Core | Accounts | Post-MVP / enhancements |
|---|---|---|---|
| Alas | Admin; Parent/Guardian | Invoices; shared files | HR/payroll; staff attendance; CSV bulk import; saved filter presets; 2FA |
| Rajiya | Student | Payments; receipts | Inventory/stock; procurement; transportation |
| Hasfa | Reports | Dashboard; portals; PDF; access tests | Library; LMS; dashboard charts; report PDF export |
| Sumaiya | Teacher | Term automation; commands; events | Messaging/SMS; transcripts/CGPA; daily absentee digest |
| Shilfa | — | Fee structures; fee items | Finance/accounting ledger; online payment gateway; multi-branch |

Shared migrations, route files, roles/permissions, common models/services, and layouts require advance coordination and one agreed editor/reviewer. Feature owners should use separate branches and PRs. Exact folders and module areas are in `IGK_ERP_TEAM_MODULE_OWNERSHIP_MATRIX.md`.

**Suggested code entry points by member** (start here, then follow routes, requests, models, services, views and tests; shared files are not exclusive ownership):

| Member | Code areas to trace | Functions/logic to demonstrate |
|---|---|---|
| Alas | `InstitutionAdmin`/`SuperAdmin` and `Auth` controllers; Guardian portal; Accountant invoice controller; HR/payroll/staff-attendance/import/filter/auth files | Admin and guardian access; invoice creation/issue/cancel/PDF; employee/leave/payroll lifecycle; bulk import/filter; password/2FA and shared authorization. |
| Rajiya | Student controllers/models/views; `EnrollmentService`; Accountant payment controller and `PaymentService`; inventory, procurement and transport controllers/services | Student/guardian linking and enrollment; invoice payment/receipt/reversal; stock movement and procurement receipt; student transport assignment. |
| Hasfa | `app/Http/Controllers/Report/`; report PDF controller/views; dashboards and portals; report/access feature tests; Library/LMS routes/controllers/views | Filtered reports and CSV/PDF; dashboard/portal access checks; library circulation and LMS screens where present. |
| Sumaiya | Teacher controllers/views; attendance/marks/term-result services; jobs/events/notifications/console commands; message/transcript services; absentee mail/command/view | Teacher assignments, attendance and marks; result generation/publication; messaging/announcements; transcript/CGPA; scheduled absentee digest. |
| Shilfa | Accountant fee structure controller/requests/views; `FeeStructure`, `FeeItem`; accounting journal/report code; PayHere and branch code for roadmap items | Fee structure/item configuration; applicable fee line generation; balanced journal/report flow; sandbox gateway and branch behavior only after runtime verification. |

This code map is a presentation aid, not an instruction to edit every listed file. Confirm the current owner and review shared-file changes before implementation.

## 6. Detailed file and function catalog

> This catalog was prepared from the inspected current source tree. Class/method names are code identifiers; adjacent descriptions explain their responsibility without reproducing source code. Generated/vendor files are intentionally excluded.

### 6.1 HTTP controllers, requests, middleware, policies, and routes

#### Route registration and middleware sequence

The request entry point is `public/index.php`; `bootstrap/app.php` builds the Laravel application, registers `routes/web.php` and `routes/console.php`, aliases role/permission middleware, and adds branch-context and forced-password-change middleware to web requests. `routes/web.php` defines public/auth/account/message/notification routes and mounts the main module routes.

| Route file | Responsibility |
|---|---|
| `routes/web.php` | Home redirect, login/logout/password/2FA, account actions, shared messages/notifications, and inclusion of the role/module route groups. |
| `routes/modules/admin.php` | Institution administration endpoints; inner role/permission middleware restricts actions. |
| `routes/admin.php` | Separate admin route file present in source; not referenced by the inspected route bootstrap. |
| `routes/teacher.php` | Teacher portal routes. |
| `routes/accountant.php` | Accountant dashboard, fee structures, invoice/payment routes. |
| `routes/accounting.php` | Accounting routes; separate file is not included by inspected `web.php`. |
| `routes/registrar.php` | Registrar dashboard/routes. |
| `routes/student.php` | Student portal routes. |
| `routes/guardian.php` | Guardian/parent portal routes. |
| `routes/library.php` | Admin/librarian library routes with granular library permissions. |
| `routes/procurement.php` | Admin/accountant supplier and purchase-order routes with granular permissions. |
| `routes/reports.php` | Role-scoped reports and exports. |
| `routes/api.php` | Sanctum/student-data and biometric API route definitions; not registered by the inspected `bootstrap/app.php`. |
| `routes/console.php` | `inspire` and announcement delivery command routes; schedules announcements, daily absentee email, overdue invoice/library reminders, low-stock notices and monthly payroll. |

The module prefixes and broad role guards are `/admin` (admin/registrar, with tighter inner rules), `/teacher`, `/accountant`, `/registrar`, `/student`, `/guardian` (legacy role label `parent`), `/library` (admin/librarian), `/procurement` (admin/accountant), and `/reports` (admin/teacher/registrar). Do not claim a route file is live solely because it exists; this is a source-registration finding, not a runtime route-list verification.

#### Middleware and policy logic

All middleware expose `handle()`:

| File | Method / responsibility |
|---|---|
| `app/Http/Middleware/EnsureRole.php` | `handle()` checks Spatie roles and legacy `role`; normalizes admin labels and lets Super Admin bypass the role check. |
| `app/Http/Middleware/ForcePasswordChange.php` | `handle()` redirects users requiring a password change except on password-change/update/logout routes. |
| `app/Http/Middleware/RequirePermissionOrAdmin.php` | `handle()` permits Super Admin or any permission from a `|`-separated permission list. |
| `app/Http/Middleware/ResolveBranchContext.php` | `handle()` prepares branch selection/context, repairs invalid selected branch, and shares branch state with views. |
| `app/Http/Middleware/SetCurrentAcademicContext.php` | `handle()` shares current year/term with views outside console; source inspection says it is not appended to the active web middleware group. |

Policy files: `AcademicYearPolicy`, `CurriculumPolicy`, `DepartmentPolicy`, `LibraryPolicy`, `MarkPolicy`, `ProgramPolicy`, `RolePolicy`, `SchoolClassPolicy`, `SettingPolicy`, `StudentPolicy`, `SubjectPolicy`, `TermPolicy`, and `UserPolicy`. The core resource policies use Laravel's `viewAny`, `view`, `create`, `update`, and `delete` methods to check matching permission names. `AcademicYearPolicy` and `TermPolicy` also check `setCurrent`; `UserPolicy` also checks `restore`/`forceDelete`; `LibraryPolicy` checks `library.*` abilities; `MarkPolicy.enter` additionally checks teacher-to-class/subject assignment. `AppServiceProvider` registers policies. Registration alone does not prove a specific controller invokes `authorize()` or a policy.

#### Controller file and action catalog

Controller `index/create/store/edit/update/destroy` groups perform resource listing/forms and CRUD; method names below are the additional or complete actions found in the files. A feature owner should read the named controller/service before changing behavior.

| Folder / files | Methods and responsibility |
|---|---|
| `app/Http/Controllers/` | `ApiTokenController.php` (`index/store/destroy`: personal tokens); `Controller.php` (base); `MessageController.php` (`index/create/store/show/storeReply`: conversations); `NotificationController.php` (`read/readAll`); `TranscriptController.php` (`student/studentPdf/guardian/guardianPdf`). |
| `Accountant/` | `DashboardController.php.index`; `FeeStructureController.php.index/store/update/destroy/storeItem/destroyItem`; `InvoiceController.php.index/create/store/issue/issueDrafts/show/cancel/pdf`; `PaymentController.php.create/store/receipt/receiptPdf/reverse`. |
| `Admin/` | `DashboardController.php.index`; `ClassSubjectController.php.index/store/destroy`; `GuardianController.php`, `SchoolClassController.php`, `StudentController.php`, `SubjectController.php`, `TeacherController.php`: resource CRUD actions. |
| `Api/V1/` | `BiometricEventController.php.store`; `StudentDataController.php.attendance/results/invoices`. |
| `Auth/` | `ForgotPasswordController.php.showLinkRequestForm/sendResetLinkEmail`; `LoginController.php.showLoginForm/login/logout`; `PasswordChangeController.php.showChangeForm/update`; `ResetPasswordController.php.showResetForm/reset`; `TwoFactorController.php.showChallenge/verifyChallenge/cancel/showSetup/confirmSetup/completeSetup/regenerateRecoveryCodes`. |
| `Guardian/` | `DashboardController.php.index`; `ChildAttendanceController.php.show`; `ChildHostelController.php.show`; `ChildResultController.php.show/reportCard/downloadReportCard`; `InvoiceController.php.index`. |
| `InstitutionAdmin/` | `AcademicYearController.php.index/create/edit/store/update/setCurrent/destroy`; `TermController.php.index/create/edit/store/update/setCurrent/destroy`; `DepartmentController.php`, `ProgramController.php`, `CurriculumController.php`, `SubjectController.php`, `SchoolClassController.php`: resource CRUD; `ClassSubjectController.php.index/store/destroy`; `StudentController.php` CRUD plus `export/dropClassSubjectEnrollment/uploadDocument/downloadDocument/destroyDocument`; `TeacherController.php`, `GuardianController.php`, `UserController.php`: CRUD. |
| `InstitutionAdmin/` continued | `SettingController.php` CRUD; `AnnouncementController.php.index/store/edit/update/destroy`; `AuditLogController.php.index`; `BulkImportController.php.index/template/store`; `DashboardController.php.index`; `ExamController.php.results/index/create/store/edit/update/destroy/publish/unpublish/studentReportCard/downloadStudentReportCard`; `MarkEntryController.php.store`; `GradeScaleController.php.edit/update`; `TermResultController.php.index/generate/submit/publish/unpublish`; `ResultPublicationController.php.publish`; `TimetableController.php.index/store/destroy`. |
| `InstitutionAdmin/` continued | `BranchController.php.index/switchContext/create/store/edit/update/destroy`; `AccountController.php` CRUD; `JournalEntryController.php.index/create/store/show/destroy/sync`; `CashflowEntryController.php.create/store`; `AccountingReportController.php.incomeExpense/trialBalance/ledger/branchSummary`; `CafeteriaController.php.index/storeItem/updateItem/destroyItem/fulfill/cancel`; `HostelController.php.index/storeHostel/updateHostel/destroyHostel/storeRoom/updateRoom/destroyRoom/createAssignment/editAssignment/storeAssignment/updateAssignment/endAssignment`. |
| `InstitutionAdmin/` continued | `HrPayrollController.php.employees/employeeCreate/employeeStore/employeeEdit/employeeUpdate/employeeDestroy/leaves/leaveStore/leaveReview/payrollIndex/payrollGenerate/payrollShow/payrollEntryUpdate/payrollApprove/payrollPaid`; `InventoryController.php` CRUD plus `movement`; `StaffAttendanceController.php.index/store`; `TransportController.php.index/routeCreate/routeStore/routeEdit/routeUpdate/routeDestroy/vehicleCreate/vehicleStore/vehicleEdit/vehicleUpdate/vehicleDestroy/stopStore/stopUpdate/stopDestroy/assignmentCreate/assignmentEdit/assignmentStore/assignmentUpdate/assignmentEnd`; `SavedFilterController.php.storeStudents/storeInvoices/destroyStudents/destroyInvoices`; `ReportController.php.index/studentRegister`. |
| `Library/` | `LibraryController.php.index/storeBook/edit/update/destroy/storeCopy/destroyCopy/borrow/returnBook/payFine`; `LibraryMasterController.php.authors/authorStore/authorUpdate/authorDestroy/categories/categoryStore/categoryUpdate/categoryDestroy/copies/copyUpdate/loans/loanUpdate/loanDestroy/fines/fineUpdate/fineDestroy`. |
| `Procurement/` | `PurchaseOrderController.php.index/create/store/show/edit/update/destroy/submit/approve/receive/cancel`; `SupplierController.php` resource CRUD. |
| `Registrar/` | `DashboardController.php.index`. |
| `Report/` | `AttendanceReportController.php`, `AttendanceSummaryPerStudentReportController.php`, `ClassAttendanceSummaryReportController.php`, `ClassResultSummaryReportController.php`, `DefaultersReportController.php`, `EnrollmentRegisterReportController.php`, `FeeCollectionReportController.php`, `ResultReportController.php`, `StudentFinancialLedgerReportController.php`, `SubjectPerformanceReportController.php`: `index/csv`; `StudentReportController.php.index/pdf/csv`; `ReportPdfController.php.download/downloadStudentLedger`. |
| `Student/` | `AttendanceController.php.index`; `CafeteriaController.php.index/store/cancel`; `DashboardController.php.index`; `ElectiveController.php.index/register/drop`; `HostelController.php.index`; `InvoiceController.php.index/pdf`; `LmsController.php.index/show/downloadMaterial/submit`; `PayHereController.php.create/store/notify/returned/cancelled`; `ProfileController.php.show/edit/downloadDocument/update`; `ResultController.php.index/reportCard/downloadReportCard`; `TimetableController.php.index`. |
| `SuperAdmin/` | `RoleController.php.index/create/store/edit/update/destroy/assignPermissions`. |
| `Teacher/` | `AttendanceController.php.create/store/index`; `ClassController.php.index/students`; `DashboardController.php.index`; `ExamController.php.index/create/store`; `LmsController.php.index/storeCourse/show/updateCourse/destroyCourse/storeLesson/updateLesson/destroyLesson/storeMaterial/downloadMaterial/destroyMaterial/storeAssignment/updateAssignment/destroyAssignment/gradeSubmission`; `MarkController.php.edit/update/show/summary`; `TimetableController.php.index`. |

#### Form Request catalog

All Form Requests generally define `authorize()` and `rules()`; they move permission and input validation out of controllers. Each class listed below is a `.php` file of the same name:

- Root: `StoreLibraryBookRequest`, `StoreLibraryCopyRequest`, `StoreLibraryLoanRequest`, `UpdateLibraryBookRequest`.
- `Accountant/`: `CancelInvoiceRequest`, `FeeStructureRequest`, `GenerateInvoicesRequest`, `RecordPaymentRequest`, `ReversePaymentRequest`.
- `Auth/`: `LoginRequest`, `PasswordChangeRequest`.
- `InstitutionAdmin/`: `AcademicYearRequest`, `AnnouncementRequest`, `BulkImportRequest`, `StoreCurriculumRequest`, `StoreDepartmentRequest`, `StoreProgramRequest`, `StoreSettingRequest`, `StoreStaffAttendanceRequest`, `StoreStudentRequest`, `StoreUserRequest`, `TermRequest`, `TimetableSlotRequest`, `UpdateCurriculumRequest`, `UpdateDepartmentRequest`, `UpdateProgramRequest`, `UpdateSettingRequest`, `UpdateStudentRequest`, `UpdateUserRequest`, `UploadStudentDocumentRequest`.
- `Inventory/`: `StoreInventoryItemRequest`, `StoreInventoryMovementRequest`, `UpdateInventoryItemRequest`.
- `Procurement/`: `CancelPurchaseOrderRequest`, `ReceivePurchaseOrderRequest`, `StorePurchaseOrderRequest`, `StoreSupplierRequest`, `UpdatePurchaseOrderRequest`, `UpdateSupplierRequest`.
- `SuperAdmin/`: `StoreRoleRequest`, `UpdateRoleRequest`.
- `Teacher/`: `StoreAttendanceRequest`, `UpdateMarksRequest`.
- `Transport/`: `StoreStudentTransportAssignmentRequest`, `StoreTransportRouteRequest`, `StoreTransportStopRequest`, `StoreTransportVehicleRequest`.

Validation includes finance amounts/dates/reasons and invoice scope, student/academic relationships, import/upload limits, stock quantity rules, procurement state changes, role permissions, teacher assignment and attendance/marks, transport capacity, and library eligibility. Custom hooks: student and curriculum requests normalize in `passedValidation()`; `StoreStudentRequest`/`UpdateStudentRequest` add validator hooks; staff attendance adds an `after()` hook; timetable and teacher attendance/marks normalize in post-validation; library loan adds `after()` and book requests normalize; password change provides custom messages.

### 6.2 Models, services, events, jobs, observers, and support logic

#### Model file catalog

Most models are Eloquent record/relationship definitions; the listed relation methods expose joins between modules, while domain transitions belong in services.

| File in `app/Models/` | Key methods / model logic |
|---|---|
| `AcademicYear.php` | `terms()`, `scopeCurrent()` |
| `Account.php` | `parent()`, `lines()`, `isDebitNormal()` |
| `Announcement.php` | `creator()`, `program()`, `schoolClass()`, `scopeActiveForRole()`, `scopeActiveForUser()` |
| `Attendance.php` | `student()`, `schoolClass()`, `session()`, `marker()` |
| `AttendanceSession.php` | `schoolClass()`, `classSubject()`, `term()`, `taker()`, `records()` |
| `AuditLog.php` | `user()` |
| `BiometricDevice.php` | `events()` |
| `BiometricEvent.php` | `device()`, `student()` |
| `Branch.php` | `booted()` applies assigned-branch scope; soft deletes |
| `CafeteriaItem.php` | `inventoryItem()`, `orderItems()` |
| `CafeteriaOrder.php` | `student()`, `items()` |
| `CafeteriaOrderItem.php` | `order()`, `cafeteriaItem()` |
| `ClassSubject.php` | `schoolClass()`, `subject()`, `teacher()`, `term()`, `learningCourse()`, `students()`, `timetableSlots()` |
| `Curriculum.php` | `program()`, `subject()` |
| `Department.php` | `programs()`, `subjects()`, `teachers()`, `head()` |
| `Employee.php` | Branch scope; `user()`, `teacher()`, `department()`, `leaveRequests()`, `payrollEntries()` |
| `Exam.php` | `class()`, `subject()`, `term()`, `results()`, `creator()` |
| `FeeItem.php` | `feeStructure()` |
| `FeeStructure.php` | Resolves legacy/program/year scope on save; `schoolClass()`, `term()`, `program()`, `academicYear()`, `isProgramWide()`, `scopeBillableFor()`, `items()`, `billableLines()`, `syncAmountFromItems()`, `isReferencedByInvoices()` |
| `GradeScale.php` | `items()` |
| `GradeScaleItem.php` | `gradeScale()` |
| `Guardian.php` | `user()`, `students()`, `primaryStudents()`, `scopeInActiveBranch()` |
| `Hostel.php` | `rooms()` |
| `HostelAssignment.php` | `student()`, `room()`, `academicYear()` |
| `HostelRoom.php` | `hostel()`, `assignments()` |
| `InventoryItem.php` | `movements()`, `isLowStock()` |
| `InventoryMovement.php` | `item()`, `recorder()` |
| `Invoice.php` | Branch scope; `student()`, `term()`, `items()`, `payments()`, `isCancelled()`, `isDraft()`, `scopeLive()`, status badge accessor |
| `InvoiceItem.php` | `invoice()`, `feeStructure()` |
| `JournalEntry.php` | Branch scope; `lines()`, `branch()` |
| `JournalLine.php` | `entry()`, `account()` |
| `LeaveRequest.php` | `employee()`, `reviewer()` |
| `LibraryAuthor.php` | `books()` |
| `LibraryBook.php` | `author()`, `category()`, `copies()` |
| `LibraryCategory.php` | `books()` |
| `LibraryCopy.php` | `book()`, `loans()` |
| `LibraryFine.php` | `loan()`, `collector()` |
| `LibraryLoan.php` | `copy()`, `student()`, `issuer()`, `returner()`, `fine()` |
| `LmsAssignment.php` | `course()`, `submissions()` |
| `LmsCourse.php` | `classSubject()`, `lessons()`, `assignments()`, `materials()` |
| `LmsCourseMaterial.php` | `course()`, `uploader()` |
| `LmsLesson.php` | `course()` |
| `LmsSubmission.php` | `assignment()`, `student()`, `grader()` |
| `MessageConversation.php` | `creator()`, `participants()`, `messages()` |
| `Payment.php` | `invoice()`, `receiver()`, `reversal()`, `isReversed()` |
| `PaymentReversal.php` | `payment()`, `invoice()`, `reverser()` |
| `PayHereTransaction.php` | `invoice()` |
| `PayrollEntry.php` | `run()`, `employee()` |
| `PayrollRun.php` | `entries()`, `generator()`, `approver()`, `payoutAccount()` |
| `Program.php` | `department()`, `gradeScale()`, `subjects()`, `students()` |
| `PurchaseOrder.php` | `supplier()`, `creator()`, `approver()`, `receiver()`, `items()` |
| `PurchaseOrderItem.php` | `purchaseOrder()`, `inventoryItem()` |
| `Result.php` | `exam()`, `student()` |
| `SavedFilter.php` | `user()` |
| `SchoolClass.php` | `students()`, `classSubjects()`, `exams()`, `program()`, `academicYear()`, `getFullNameAttribute()`; default capacity and formatted name |
| `SchoolMessage.php` | `conversation()`, `sender()` |
| `Setting.php` | Static `get()` / `set()` read and write settings |
| `StaffAttendance.php` | `teacher()`, `recorder()` |
| `Student.php` | Mirrors current document number to legacy `national_id`; `booted()` adds branch scope; relations to user/class/subjects/guardians/program/year/attendance/results/documents/invoices/transport/hostel |
| `StudentDocument.php` | `student()`, `uploader()`, readable-size and type-name accessors |
| `StudentTransportAssignment.php` | `student()`, `route()`, `stop()`, `academicYear()` |
| `Subject.php` | `classSubjects()`, `programs()`, `exams()`, `subjectResults()` |
| `SubjectResult.php` | `term()`, `schoolClass()`, `student()`, `subject()` |
| `Supplier.php` | `purchaseOrders()` |
| `Teacher.php` | `user()`, `department()`, `classSubjects()` |
| `Term.php` | `academicYear()`, `exams()`, `subjectResults()`, `termResults()`, `scopeCurrent()`; casts sequence/dates/current flag |
| `TermResult.php` | `term()`, `schoolClass()`, `student()` |
| `TermResultGeneration.php` | `term()`, `schoolClass()` |
| `TimetableSlot.php` | `classSubject()`, `getDayNameAttribute()` |
| `TransportRoute.php` | `vehicle()`, `stops()`, `assignments()` |
| `TransportStop.php` | `route()`, `assignments()` |
| `TransportVehicle.php` | `routes()` |
| `User.php` | Student/branch/teacher/guardian relationships; role/admin checks, `requiresTwoFactor()`, `dashboardRoute()` |
| `Scopes/BranchOwned.php` | Global branch scope; filters unassigned staff and selected branch/global admin data. |

Together these relationships form the school data graph: students link to class/program/year, guardians, enrollment, attendance, results, documents, invoices, transport and hostel; class-subject offerings link to teacher, term, LMS, timetable and student enrollment; invoices link to student/term/lines/payments; employees link to users/teachers/departments/leave/payroll; purchase orders link suppliers and inventory movements.

#### Services and business rules

| File/class | Methods / logic |
|---|---|
| `AnnouncementDeliveryService.php` | `deliver`, `deliverDue`; `recipientQuery`, `applyStudentScope`, `applyClassScope` target users and deliver due/unexpired announcements once. |
| `AttendanceSessionService.php` | `enrolledStudentIds`, `create`, `verifyEditWindow` resolve enrollment, avoid duplicate sessions, and enforce locked/48-hour edit windows except admin/registrar. |
| `BulkCsvImportService.php` | `templateHeaders`, `inspect`, `inspectPath`, `import`, `validateRows`, `result`, `normalizeHeader`, `isBlankRow`: validates supported CSV types and imports up to 5,000 rows transactionally. |
| `CafeteriaOrderService.php` | `placeOrder`, `fulfill`, `cancel` reserve, issue, or restore stock with item locks. |
| `ConfiguredHttpSmsGateway.php` | `send` posts an authenticated JSON request to configured SMS endpoint. |
| `ElectiveRegistrationService.php` | `currentTermFor`, `offeringsFor`, `registrationIsOpen`, `register`, `drop`; checks eligibility, registration dates, capacity and timetable clash. |
| `EnrollmentService.php` | `enrollStudent`, `syncClassSubjects`, `syncGuardians`, `dropStudent`: transactionally check capacity/year/program/admission number, create/link student login, synchronize guardians/required offerings, audit; dropping removes local documents and related user/student records. |
| `GradeScaleService.php` | `grade`, `gradePoint`, `isPassing`, `defaultItems`, `items`, `clearCache`, `validateRanges`, `updateDefault`; private `itemFor`; resolves/caches grade scales and validates contiguous 0–100 ranges. |
| `GradingService.php` | `percentage`, `grade`, `gradePoint`, `isPassing`, `calculateSubjectPercentage`, `calculateTermGpa`; weighted score and credit-weighted GPA calculations exclude excused work. |
| `HostelAssignmentService.php` | `assign`, `end` validate active student/room/branch, bed capacity and date-overlap, then audit. |
| `HrPayrollService.php` | `requestLeave`, `reviewLeave`, `generatePayroll`, `updateEntry`, `approvePayroll`, `markPaid`; manages leave and draft→approved→paid payroll with separation of duties and journal posting. |
| `SriLankaPayrollCalculator.php` | `calculate` computes gross/statutory amounts; private `primaryApit` calculates progressive tax. |
| `InventoryService.php` | `recordMovement` locks stock and records receipt/issue/adjustment; rejects invalid or negative stock results. |
| `InvoiceService.php` | `nextInvoiceNumber`, `generateForStudent`, `bulkGenerateForClass`, `issue`, `issueDraftsForClass`, `cancel`; validates fees/currency/discount, is idempotent and cancels only unpaid invoices. |
| `JournalService.php` | `post`, `syncFromBilling` validate balanced journals and idempotently synchronize invoices/payments. |
| `MarksEntryService.php` | `enrolledStudentIds`, `bulkSave` transactionally saves marks/absence/excusal and blocks edits to published exams. |
| `PayHereService.php` | `createTransaction`, `checkoutHash`, `verifyNotification`, `handleNotification`, `checkoutUrl`; private `ensureSandboxEnabled`; validates signed gateway notifications and idempotently records payment. |
| `PaymentService.php` | `nextReceiptNumber`, `recordPayment`, `reversePayment` lock invoice, prevent overpayment, allocate receipt, update balance, and preserve/recompute reversals. |
| `ProcurementService.php` | `create`, `update`, `submit`, `approve`, `receive`, `cancel`, `deleteDraft`; private numbering/item/branch/transition guards; receipt updates inventory transactionally. |
| `ResultPublicationService.php` | `verifyNotLocked`, `publish`, `unpublish` require result completeness and enforce publication locks. |
| `SmsService.php` | Constructor injects `SmsGateway`; `send` validates E.164/message and delegates delivery. |
| `TermResultService.php` | `enrolledStudentCount`, `generate`, `submitForApproval`, `publishClass`, `unpublishClass`; generates results and governs locked draft→approval→published transitions. |
| `TranscriptService.php` | `forStudent` aggregates published term/year GPA and credits. |
| `TransportService.php` | `saveVehicle`, `saveRoute`, `addStop`, `assignStudent`, `removeStop`, `updateStop`, `deleteRoute`, `deleteVehicle`; private seat-capacity calculation and referenced-delete rules. |

#### Shared support, providers, and lifecycle hooks

- `AdmissionNumberGenerator.php`: `next`, `prefix` and private normalization/collision checks create year/program-aware admission IDs.
- `AuditLogger.php`: `log` writes actor, subject, action and old/new values.
- `BranchContext.php`: `hasActiveBranches`, `isGlobalAdmin`, `activeBranchId` resolve tenant/branch context.
- `CsvStreamer.php`: `download` streams mapped rows as CSV.
- `InstitutionDetails.php`: `values` supplies institution data to documents/views.
- `InternationalPhoneNumber.php`: `normalizeRequest`, `normalize` normalize international phone numbers to E.164 when possible.
- `InvoiceNumberGenerator.php`: `next` legacy configurable-prefix invoice sequence. **Verify before presenting:** `InvoiceService` uses `NumberSequence` (`INV-YYYY-####`), while this helper uses configurable prefix/two-digit year/six-digit sequence.
- `InvoicePdf.php`: `download` renders/downloads an invoice PDF.
- `Money.php`: BCMath `add`, `sub`, `mul`, `div`, `comp`, `hasValidScale`, `format`, `sumByCurrency`, `formatTotals`, `hasPositiveTotal`; private decimal conversion.
- `NumberSequence.php`: `next`, `highestSuffix` allocate a locked sequence.
- `NumberToWords.php`: `rupees` converts amount to words; private `integer` formats integer portion.
- `ReceiptNumberGenerator.php`: `next` allocates a locked per-year receipt number.
- `ReportCardData.php`: `forStudent` assembles published results, grade scale and attendance.
- `SchoolHelper.php`: `summaryFromCounts`, `attendanceSummary`, `attendancePercentage`, `resultPercentage`, `gradeFor`, `studentAverage`, `teacherClassIds`, `authorizeTeacherClass`, `authorizeTeacherClassSubject`, `allowedClasses`, `formatMarks`, `gradeBadge`, `gradePointFor`, `calculateTermGpa`.
- `TimetableClashDetector.php`: `checkClash` rejects room/class/teacher overlaps.

`BranchOwned.php` is the global branch scope; `BranchIsolationServiceProvider.boot()` attaches it to branch-owned models and stamps/validates branch IDs. `AuditObserver.php` exposes `created`, `updated`, `deleted` and private `hasActor`/`safeValues`; it audits actor-backed mutations while filtering passwords, tokens, secrets and timestamps. `AppServiceProvider.register()` binds `SmsGateway`; `boot()` configures password/rate limits, registers policies and attaches selected audit observers.

**Events** (`app/Events/`): `AnnouncementPublished.php`, `InvoiceIssued.php`, `PaymentRecorded.php`, `ResultPublished.php`, `StudentAdmitted.php`, `TermResultsPublished.php`. The invoice/payment/term-result events carry their entity/actor data; invoice, payment and term-result publication events are after-commit. Payload events should not be presented as proof a listener is wired.

**Jobs** (`app/Jobs/`): `GenerateMonthlyPayroll.php.handle()` creates a draft monthly payroll; `GenerateTermInvoices.php.handle/result()` runs bulk billing and caches status; `GenerateTermResults.php.handle/failed()` updates generation/error state; `NotifyLowStockItems.php.handle()` alerts admins; `NotifyOverdueLibraryLoans.php.handle()` throttles loan reminders; `ProcessBulkCsvImport.php.handle/result()` processes and caches import status; `SendOverdueInvoiceReminders.php.handle()` throttles student/guardian reminders.

**Listeners** (`app/Listeners/`): `SendInvoiceIssuedNotification.php.handle()` and `SendPaymentReceivedNotification.php.handle()` notify the student and report delivery exceptions.

**Mail** (`app/Mail/`): `DailyAbsenteeDigest.php.build()` builds a date-specific registrar absentee digest email.

**Notifications** (`app/Notifications/`): `AnnouncementEmail.php` (`via/toMail`); `AnnouncementPublished.php` (`via/toDatabase`); `InvoiceIssuedNotification.php`, `InvoiceOverdueNotification.php`, and `PaymentReceivedNotification.php` (`via/toMail/toDatabase`); `LibraryLoanOverdueNotification.php`, `LowStockNotification.php`, `SchoolMessageReceived.php`, `TermResultsPublished.php` (`via/toDatabase`). These deliver through configured Laravel notification channels.

**Validation rules** (`app/Rules/`): `UniqueStudentDocumentNumber.php.validate()` checks both document fields; `ValidInternationalPhone.php.validate()` checks phone/optional region; `ValidPersonName.php.validate()` checks person names; `ValidStudentDocument.php.validate()` applies country/document-type format.

**Enums** (`app/Enums/`): `AttendanceStatus.php` (`Present/Absent/Late/Excused`); `EnrollmentStatus.php` (`Enrolled/Dropped/Completed`); `ExamStatus.php` (`Draft/Scheduled/Ongoing/Marking/Published`); `InvoiceStatus.php` (`Draft/Issued/PartiallyPaid/Paid/Cancelled`); `PaymentMethod.php` (`Cash/BankTransfer/Cheque`); `ResultStatus.php` (`Draft/PendingApproval/Published/Withheld`); `StudentStatus.php` (`Applicant/Active/Suspended/Graduated/Withdrawn`); `SchoolEnums.php` aggregates several statuses. Duplicate enum names across files are an autoload/refactor risk to verify.

**Exceptions** (`app/Exceptions/`): `EnrollmentException.php`, `MarksLockedException.php`, `PaymentExceedsBalanceException.php` (with static `forBalance()`), and `DomainExceptions.php`. Aggregate and standalone files redeclare names with different base classes; verify which declaration is loaded before changing exception handling.

**Providers** (`app/Providers/`): `AppServiceProvider.php.register/boot`; `BranchIsolationServiceProvider.php.boot`. `bootstrap/providers.php` lists these two providers. Listener auto-discovery/runtime wiring was not confirmed from provider registration alone.

**Contract** (`app/Contracts/SmsGateway.php`): the `SmsGateway.send(string $to, string $message, ?string $reference): void` interface decouples SMS business logic from the configured HTTP gateway implementation.

**Console commands** (`app/Console/Commands/`): `CreateBiometricDevice.php.handle()` creates a device and displays its one-time credential; `EmailDailyAbsentees.php.handle()` emails active registrars; `ImportCsv.php.handle()` supports validation, dry-run, commit and queue modes; `VerifyIntegrity.php.handle()` checks invoice/payment/line/balance/status consistency read-only.

Service transactions enforce domain invariants, but services generally do not authorize the caller; authorization must be verified in the route/controller/policy layer. Event-listener registration and schedule wiring should be checked in runtime configuration before claiming those jobs automatically run.

### 6.3 Migrations, seeders, factories, and test coverage

#### Database schema evolution

`database/migrations/` contains 66 files. They evolve the schema from Laravel base tables into school, academic, finance, and operations modules. Exact filenames and primary changes:

Each migration class defines Laravel schema lifecycle methods (`up()` to apply and `down()` to roll back); the table summarizes the `up()` effect. The two retired no-op migrations intentionally do not recreate duplicate schema.

| Migration file | Primary schema change |
|---|---|
| `0001_01_01_000000_create_users_table.php` | Creates `users`, `password_reset_tokens`, and `sessions`. |
| `0001_01_01_000001_create_cache_table.php` | Creates `cache` and `cache_locks`. |
| `0001_01_01_000002_create_jobs_table.php` | Creates `jobs`, `job_batches`, and `failed_jobs`. |
| `2026_01_01_000001_add_role_to_users_table.php` | Adds legacy role field to users. |
| `2026_01_01_000002_create_classes_table.php` | Creates school classes. |
| `2026_01_01_000003_create_subjects_table.php` | Creates subjects. |
| `2026_01_01_000004_create_teachers_table.php` | Creates teacher profiles. |
| `2026_01_01_000005_create_guardians_table.php` | Creates guardian profiles. |
| `2026_01_01_000006_create_students_table.php` | Creates student profiles. |
| `2026_01_01_000007_create_class_subject_table.php` | Creates class/subject pivot. |
| `2026_01_01_000008_create_attendance_table.php` | Creates attendance records. |
| `2026_01_01_000009_create_exams_table.php` | Creates exams. |
| `2026_01_01_000010_create_results_table.php` | Creates exam results. |
| `2026_01_01_000011_upgrade_core_tables_for_sop.php` | Creates academic years/terms and extends users, classes, subjects, attendance, exams and results. |
| `2026_01_01_000012_create_student_documents_table.php` | Creates student documents. |
| `2026_01_01_000013_create_timetable_slots_table.php` | Creates timetable slots. |
| `2026_01_01_000014_create_finance_tables.php` | Creates fee structures, invoices and payments. |
| `2026_01_01_000015_create_announcements_table.php` | Creates announcements. |
| `2026_01_01_000016_create_audit_logs_table.php` | Creates audit logs. |
| `2026_01_01_000017_create_rbac_tables.php` | Creates roles, permissions and pivots. |
| `2026_01_01_000018_create_departments_table.php` | Creates departments. |
| `2026_01_01_000019_create_programs_table.php` | Creates programs. |
| `2026_01_01_000020_create_curricula_table.php` | Creates curricula. |
| `2026_01_01_000021_create_settings_table.php` | Creates settings. |
| `2026_01_01_000022_create_student_subject_enrollments_table.php` | Creates student-to-subject enrollment. |
| `2026_01_01_000023_complete_sop_academic_relationships.php` | Completes academic relations; adds guardian/student pivot, attendance sessions and notifications. |
| `2026_01_01_000024_add_soft_deletes_to_exams.php` | Adds exam soft deletes. |
| `2026_01_01_000025_add_start_time_to_attendance_sessions.php` | Adds attendance-session start time. |
| `2026_01_01_000026_add_student_identity_and_subject_enrollment_status.php` | Adds student identity fields and enrollment status. |
| `2026_01_01_000027_add_term_results_and_grade_scales.php` | Adds grade scales, subject/term results and generation records; extends results. |
| `2026_01_01_000028_repair_notifications_table.php` | Creates notifications table if missing. |
| `2026_01_01_000029_complete_accounts_module.php` | Adds account-module fields, number sequences, invoice items and payment reversals. |
| `2026_01_01_000030_add_fee_items_and_invoice_item_link.php` | Creates fee items and links them to invoice items. |
| `2026_01_01_000031_fee_structures_program_academic_year.php` | Adds program/year fee-structure scope. |
| `2026_03_01_000100_extend_users_and_core_tables_for_sop.php` | Retired no-op; duplicate earlier schema was removed. |
| `2026_03_01_000200_create_sop_academic_and_finance_tables.php` | Retired no-op; duplicate earlier schema was removed. |
| `2026_11_02_162140_create_permission_tables.php` | Creates Spatie permission/role pivots. |
| `2026_11_02_162141_create_activity_log_table.php` | Creates activity log. |
| `2026_11_04_120000_create_finance_transactions_table.php` | Creates finance transactions. |
| `2026_11_10_000001_create_private_messaging_tables.php` | Creates conversations, participants and messages. |
| `2026_11_10_090000_create_library_tables.php` | Creates library authors, categories, books, copies, loans and fines. |
| `2026_11_10_090100_create_staff_attendance_table.php` | Creates staff attendance. |
| `2026_11_10_100000_create_inventory_tables.php` | Creates inventory items and movements. |
| `2026_11_10_100100_create_procurement_tables.php` | Creates suppliers, purchase orders and line items. |
| `2026_11_10_110000_create_hr_payroll_tables.php` | Creates employees, leave requests, payroll runs and entries. |
| `2026_11_10_120000_create_transport_management_tables.php` | Creates vehicles, routes, stops and student assignments. |
| `2026_11_10_130000_create_saved_filters_table.php` | Creates saved filters. |
| `2026_11_10_140000_add_two_factor_authentication_to_users_table.php` | Adds 2FA fields to users. |
| `2026_11_10_150000_create_learning_management_tables.php` | Creates LMS courses, lessons, assignments and submissions. |
| `2026_11_10_160000_create_hostel_management_tables.php` | Creates hostels, rooms and assignments. |
| `2026_11_10_170000_create_cafeteria_tables.php` | Creates cafeteria items, orders and order items. |
| `2026_11_11_000100_create_branches_and_accounting_tables.php` | Creates branches, accounts, journals/lines; adds branch IDs to selected tables. |
| `2026_11_11_000101_create_payhere_transactions_table.php` | Creates PayHere transactions. |
| `2026_11_11_000102_create_biometric_ingestion_tables.php` | Creates biometric devices and events. |
| `2026_11_11_000103_add_sri_lanka_payroll_fields.php` | Adds Sri Lankan payroll fields. |
| `2026_11_11_000104_add_payroll_payout_account.php` | Adds payroll payout account. |
| `2026_11_11_000105_add_branch_assignment_to_users.php` | Adds user branch assignment. |
| `2026_11_11_103344_create_personal_access_tokens_table.php` | Creates personal access tokens. |
| `2026_11_12_000100_extend_branch_isolation_to_operational_tables.php` | Adds operational branch IDs and backfills to main branch when available. |
| `2026_11_12_000200_scope_inventory_sku_uniqueness_to_branch.php` | Makes inventory SKU uniqueness branch-scoped. |
| `2026_11_12_120000_create_lms_course_materials_table.php` | Creates LMS course materials. |
| `2026_11_12_210000_add_international_education_and_currency_fields.php` | Adds international education/nationality and currency fields. |
| `2026_11_12_210100_add_currency_to_journal_entries.php` | Adds currency to journal entries. |
| `2026_11_13_070000_add_student_document_identifiers.php` | Adds student document identifiers. |
| `2026_11_13_140100_expand_academic_identifiers.php` | Expands academic class/subject identifiers. |
| `2026_11_13_154100_expand_library_category_name.php` | Expands library category-name length. |

Never edit an already-applied migration to update shared data; add a new migration and review its rollback and branch-scope behavior.

#### Seeding and local demo data

`DatabaseSeeder.run()` sends `production` to `ProductionSeeder`; sends `local` and `testing` to `FullSampleDataSeeder`; rejects any other environment. The local/test order is:

1. `DemoDataSeeder` — roles, settings, academic structure and main synthetic people/school records; also initializes local credentials and related demo modules.
2. `CompleteSampleDataSeeder` — fee items/curriculum/library/messages/documents and supplemental data.
3. `BranchAccountingSeeder` — branches/chart of accounts/journals.
4. `LibraryBranchDemoSeeder` and `CafeteriaStudentDemoSeeder` — branch-visible loans/fines and student orders.
5. `TeacherLearningDemoSeeder` — sample learning/exam/course content and PDFs.
6. `ExtendedScopeDemoSeeder` — pending online payment and related test/demo data.

`ProductionSeeder` intentionally seeds only production baseline settings, roles/permissions, grade scale and Super Admin; it does not load the synthetic full sample dataset. `PerfSeeder` is separate and creates performance data; it is not part of the ordinary `DatabaseSeeder` chain. Demo credential details are in `sample dataset.md`; never use demo credentials in production.

Exact seeder files under `database/seeders/` (entry method is `run()`):

| Seeder file | Purpose |
|---|---|
| `AcademicStructureSeeder.php` | Creates academic structure and delegates grade-scale setup. |
| `BranchAccountingSeeder.php` | Seeds local branch/account chart/journal demo data. |
| `CafeteriaStudentDemoSeeder.php` | Seeds branch-visible cafeteria menus and student orders locally. |
| `CompleteSampleDataSeeder.php` | Completes demo fee items, curriculum, library, announcements, reversal, messages and documents. |
| `DatabaseSeeder.php` | Environment-based production/local/testing dispatch. |
| `DemoDataSeeder.php` | Main local/test synthetic accounts, academics and school data. |
| `ExtendedScopeDemoSeeder.php` | Adds online-payment, biometric and token sample records locally. |
| `FullSampleDataSeeder.php` | Ordered full local/testing demo-data pipeline. |
| `GradeScaleSeeder.php` | Seeds grade scales; invokes international grade scale. |
| `HostelCafeteriaDemoSeeder.php` | Seeds hostel/cafeteria demo records. |
| `InternationalGradeScaleSeeder.php` | Seeds international grade bands. |
| `LibraryBranchDemoSeeder.php` | Seeds branch-visible library loans and fines locally. |
| `LmsDemoSeeder.php` | Seeds LMS demo records. |
| `LocalDemoCredentialsSeeder.php` | Creates local/testing role logins, passwords and role/profile links. |
| `PerfSeeder.php` | Separate local/testing performance dataset; not called by `DatabaseSeeder`. |
| `ProductionSeeder.php` | Production-only baseline settings, roles/permissions, grade scale and super-admin. |
| `RolePermissionSeeder.php` | Defines the role/permission configuration. |
| `SettingSeeder.php` | Seeds institution settings. |
| `SuperAdminSeeder.php` | Creates bootstrap super-admin without resetting an existing rotated password. |
| `TeacherLearningDemoSeeder.php` | Seeds teacher exams/course modules/past-paper files locally. |
| `TeacherSubjectAssignmentSeeder.php` | Assigns demo teachers to class/subject offerings. |

The dispatch/ordering logic differs by entry point: use `DatabaseSeeder` for the whole environment-aware workflow; use an individual class only for focused setup/test needs, and obey that class's environment guard and prerequisites.

#### Factories and test suites

There are 16 model factories in `database/factories/`; each defines model defaults through `definition()`:

| Factory file | Model |
|---|---|
| `AcademicYearFactory.php` | Academic year |
| `DepartmentFactory.php` | Department |
| `FeeStructureFactory.php` | Fee structure |
| `GuardianFactory.php` | Guardian |
| `InventoryItemFactory.php` | Inventory item |
| `InventoryMovementFactory.php` | Inventory movement |
| `InvoiceFactory.php` | Invoice |
| `PaymentFactory.php` | Payment |
| `ProgramFactory.php` | Program |
| `SchoolClassFactory.php` | School class |
| `StudentDocumentFactory.php` | Student document |
| `StudentFactory.php` | Student |
| `SubjectFactory.php` | Subject |
| `TeacherFactory.php` | Teacher |
| `TermFactory.php` | Term |
| `UserFactory.php` | User |

`phpunit.xml` sets `APP_ENV=testing`, SQLite `:memory:`, array cache/session/mail, and synchronous queue. `tests/TestCase.php` fakes the password-compromise API. Therefore `php artisan test` is isolated from the developer's persistent local MySQL demo database.

Feature tests (`tests/Feature/`):

| Test file | Main scenario |
|---|---|
| `AccountantFinanceAccessTest.php` | Accountant dashboard/invoice/payment actions and finance-vs-academic access. |
| `AccountingReportTest.php` | Income/expense totals and account currencies. |
| `AccountsModuleCompletionTest.php` | Invoice, discounts, receipts, reversals, cancellation, integrity and access. |
| `AuditObserverTest.php` | Audit model changes without storing passwords. |
| `ConcurrentPaymentsTest.php` | Parallel payment locks, unique receipts and exact balances. |
| `CurriculumModuleTest.php` | Curriculum CRUD and teacher access denial. |
| `EnrollmentAndMarksServiceTest.php` | Enrollment/capacity and marks services. |
| `ExampleTest.php` | Basic app response. |
| `FeeItemsAndDraftInvoicesTest.php` | Required/optional fees and draft/issue flow. |
| `FeeStructureImmutabilityTest.php` | Fee edits after invoicing are restricted. |
| `FinanceEndToEndWorkflowTest.php` | Fees→invoice→payment→receipt→report consistency. |
| `FinanceInvoiceWorkflowTest.php` | Numbering/idempotency/jobs and invoice failure behavior. |
| `HrEmployeeEditTest.php` | Employee update normalization and validation. |
| `InternationalCsvValidationTest.php` | International CSV, documents and phone input. |
| `InternationalDemoSeederTest.php` | Full international demo dataset and idempotency. |
| `ModuleInputValidationTest.php` | Library/procurement/accounting/roles/branch/announcement input. |
| `PasswordResetSecurityTest.php` | Reset-link privacy, expiry, single use and throttling. |
| `PdfDocumentsTest.php` | Student/guardian ownership and admin PDF downloads. |
| `PerformanceSeederTest.php` | Performance dataset realism and follow-on seeders. |
| `Phase1AuthAndAcademicTest.php` | Login, password change, academic invariants and throttling. |
| `Phase1RbacAndModuleTest.php` | RBAC/user management and organization settings. |
| `Phase2DocumentsCapacityTimetableAttendanceTest.php` | Admission/documents/capacity/timetable/attendance. |
| `Phase3MarksLockGpaPublicationReportCardTest.php` | Marks lock, GPA, publication visibility and report cards. |
| `Phase4FinanceModuleTest.php` | Money precision, invoice, payment and reversal. |
| `Phase5AnnouncementsAuditReportsTest.php` | Announcement targeting, audit events and CSV. |
| `PrivateMessageInputValidationTest.php` | Private-message subject/body validation. |
| `ProductionSeederTest.php` | Production seeder excludes demo data. |
| `RegistrarAccessTest.php` | Registrar read/access boundaries. |
| `ReportsParityTest.php` | CSV/PDF reports, filters, permissions and empty data. |
| `RoleLoginVerificationTest.php` | Role-specific login checks. |
| `SopRemainingAcceptanceTest.php` | Admission duplicate confirmation, document identity and report filters. |
| `StudentAccountLinkingTest.php` | Student account linking, enrollment, identity and access. |
| `StudentRegisterExportTest.php` | Filtered register CSV and throttling. |
| `SuperAdminAllCampusesTest.php` | Super Admin and Institution Admin campus scopes/totals. |
| `TeacherSubjectAssignmentSeederTest.php` | Teacher assignments and seed idempotency. |
| `TermResultsWorkflowTest.php` | Generation, missing results, publication, queue and report cards. |

Unit tests (`tests/Unit/`):

| Test file | Main scenario |
|---|---|
| `ExampleTest.php` | Basic assertion smoke test. |
| `GradingServiceTest.php` | Percentages, grade boundaries/points and GPA. |
| `InternationalInputValidationTest.php` | International input rules. |
| `MoneyTest.php` | Exact decimal arithmetic, comparison and formatting. |
| `NumberToWordsTest.php` | Currency amount-to-words. |
| `ReceiptNumberGeneratorTest.php` | Year-specific receipt numbering and uniqueness. |

`tests/TestCase.php` fakes the password-compromise lookup. `tests/Concerns/CreatesFinanceFixtures.php` builds finance fixtures independently of the seeders. The 44 test/support files are these 36 Feature tests, 6 Unit tests, `TestCase.php`, and the one Concerns trait.

### 6.4 Views, JavaScript/CSS, config, bootstrap, and deployment

#### Presentation and browser assets

The UI is mainly server-rendered Blade. `resources/views/Layouts/LayoutApp.blade.php` is the authenticated shell; shared head/error/table/stat partials and reusable `resources/views/components/` inputs/tables are included by pages. Views are grouped by role and feature:

**Exact Blade file catalog** (`resources/views/`; a template's name indicates its screen/action, not a PHP function):

- `InstitutionAdmin/`: `CafeteriaIndex.blade.php` (cafeteria); `HostelAssignmentForm.blade.php`, `HostelIndex.blade.php`; `HrEmployeeForm.blade.php`, `HrEmployeesIndex.blade.php`, `HrLeavesIndex.blade.php`, `HrPayrollIndex.blade.php`, `HrPayrollShow.blade.php`; `InstitutionAdminAcademicYearsIndex.blade.php`; `InstitutionAdminAnnouncementsEdit.blade.php`, `InstitutionAdminAnnouncementsIndex.blade.php`; `InstitutionAdminAuditLogsIndex.blade.php`; `InstitutionAdminClassSubjectsIndex.blade.php`; `InstitutionAdminClassesCreate.blade.php`, `InstitutionAdminClassesEdit.blade.php`, `InstitutionAdminClassesForm.blade.php`, `InstitutionAdminClassesIndex.blade.php`; `InstitutionAdminCurriculaCreate.blade.php`, `InstitutionAdminCurriculaEdit.blade.php`, `InstitutionAdminCurriculaIndex.blade.php`; `InstitutionAdminDashboard.blade.php`; `InstitutionAdminDepartmentsCreate.blade.php`, `InstitutionAdminDepartmentsEdit.blade.php`, `InstitutionAdminDepartmentsIndex.blade.php`; `InstitutionAdminExamsForm.blade.php`, `InstitutionAdminExamsIndex.blade.php`; `InstitutionAdminGradeScaleEdit.blade.php`; `InstitutionAdminGuardiansCreate.blade.php`, `InstitutionAdminGuardiansEdit.blade.php`, `InstitutionAdminGuardiansForm.blade.php`, `InstitutionAdminGuardiansIndex.blade.php`; `InstitutionAdminImportsIndex.blade.php`; `InstitutionAdminProgramsCreate.blade.php`, `InstitutionAdminProgramsEdit.blade.php`, `InstitutionAdminProgramsIndex.blade.php`; `InstitutionAdminSettingsIndex.blade.php`; `InstitutionAdminStaffAttendanceIndex.blade.php`; `InstitutionAdminStudentsCreate.blade.php`, `InstitutionAdminStudentsEdit.blade.php`, `InstitutionAdminStudentsForm.blade.php`, `InstitutionAdminStudentsIndex.blade.php`; `InstitutionAdminSubjectsCreate.blade.php`, `InstitutionAdminSubjectsEdit.blade.php`, `InstitutionAdminSubjectsForm.blade.php`, `InstitutionAdminSubjectsIndex.blade.php`; `InstitutionAdminTeachersCreate.blade.php`, `InstitutionAdminTeachersEdit.blade.php`, `InstitutionAdminTeachersForm.blade.php`, `InstitutionAdminTeachersIndex.blade.php`; `InstitutionAdminTermResultsIndex.blade.php`; `InstitutionAdminTermsIndex.blade.php`; `InstitutionAdminTimetableIndex.blade.php`; `InstitutionAdminUsersCreate.blade.php`, `InstitutionAdminUsersEdit.blade.php`, `InstitutionAdminUsersIndex.blade.php`; `InventoryForm.blade.php`, `InventoryIndex.blade.php`, `InventoryShow.blade.php`; `TransportAssignmentForm.blade.php`, `TransportIndex.blade.php`, `TransportRouteForm.blade.php`, `TransportVehicleForm.blade.php`.
- `Admin/`: `AdminClassSubjectsIndex.blade.php`; `AdminClassesCreate.blade.php`, `AdminClassesEdit.blade.php`, `AdminClassesForm.blade.php`, `AdminClassesIndex.blade.php`; `AdminDashboard.blade.php`; `AdminGuardiansCreate.blade.php`, `AdminGuardiansEdit.blade.php`, `AdminGuardiansForm.blade.php`, `AdminGuardiansIndex.blade.php`; `AdminStudentsCreate.blade.php`, `AdminStudentsEdit.blade.php`, `AdminStudentsForm.blade.php`, `AdminStudentsIndex.blade.php`; `AdminSubjectsCreate.blade.php`, `AdminSubjectsEdit.blade.php`, `AdminSubjectsForm.blade.php`, `AdminSubjectsIndex.blade.php`; `AdminTeachersCreate.blade.php`, `AdminTeachersEdit.blade.php`, `AdminTeachersForm.blade.php`, `AdminTeachersIndex.blade.php`.
- `Student/`: `Cafeteria.blade.php`, `Hostel.blade.php`, `LmsIndex.blade.php`, `LmsShow.blade.php`, `StudentAttendance.blade.php`, `StudentDashboard.blade.php`, `StudentElectivesIndex.blade.php`, `StudentInvoicesIndex.blade.php`, `StudentProfile.blade.php`, `StudentProfileEdit.blade.php`, `StudentReportCard.blade.php`, `StudentResults.blade.php`, `StudentTimetableIndex.blade.php`, `StudentTranscript.blade.php`.
- `Reports/`: `ReportAttendance.blade.php`, `ReportAttendanceSummaryPerStudent.blade.php`, `ReportClassAttendanceSummary.blade.php`, `ReportClassFilterNote.blade.php`, `ReportClassResultSummary.blade.php`, `ReportDefaulters.blade.php`, `ReportEnrollmentRegister.blade.php`, `ReportFeeCollection.blade.php`, `ReportResults.blade.php`, `ReportStudentFinancialLedger.blade.php`, `ReportStudents.blade.php`, `ReportSubjectPerformance.blade.php`.
- `Teacher/`: `LmsIndex.blade.php`, `LmsShow.blade.php`, `TeacherAttendanceCreate.blade.php`, `TeacherAttendanceIndex.blade.php`, `TeacherClassesIndex.blade.php`, `TeacherClassesStudents.blade.php`, `TeacherDashboard.blade.php`, `TeacherExamsCreate.blade.php`, `TeacherExamsIndex.blade.php`, `TeacherMarksEdit.blade.php`, `TeacherMarksShow.blade.php`, `TeacherTimetableIndex.blade.php`.
- `Accounting/`: `AccountsForm.blade.php`, `AccountsIndex.blade.php`, `CashflowForm.blade.php`, `IncomeExpense.blade.php`, `JournalsForm.blade.php`, `JournalsIndex.blade.php`, `JournalsShow.blade.php`, `Ledger.blade.php`, `TrialBalance.blade.php`.
- `Accountant/`: `AccountantDashboard.blade.php`, `AccountantFeeStructuresIndex.blade.php`, `AccountantInvoicesCreate.blade.php`, `AccountantInvoicesIndex.blade.php`, `AccountantInvoicesShow.blade.php`, `AccountantPaymentsCreate.blade.php`, `AccountantReceiptShow.blade.php`.
- `Auth/`: `AuthForgotPassword.blade.php`, `AuthLogin.blade.php`, `AuthPasswordChange.blade.php`, `AuthResetPassword.blade.php`, `AuthTwoFactorChallenge.blade.php`, `AuthTwoFactorSetup.blade.php`.
- `Guardian/`: `ChildHostel.blade.php`, `GuardianAttendance.blade.php`, `GuardianChildSwitch.blade.php`, `GuardianDashboard.blade.php`, `GuardianInvoicesIndex.blade.php`, `GuardianResults.blade.php`.
- `Layouts/`: `LayoutApp.blade.php`, `LayoutAttendanceTable.blade.php`, `LayoutErrors.blade.php`, `LayoutHead.blade.php`, `LayoutResultsTable.blade.php`, `LayoutStat.blade.php`.
- `Library/`: `LibraryBookEdit.blade.php`, `LibraryCopiesIndex.blade.php`, `LibraryDashboard.blade.php`, `LibraryFinesIndex.blade.php`, `LibraryLoansIndex.blade.php`, `LibraryMasterIndex.blade.php`.
- `Procurement/`: `PurchaseOrderForm.blade.php`, `PurchaseOrderShow.blade.php`, `PurchaseOrdersIndex.blade.php`, `SupplierForm.blade.php`, `SuppliersIndex.blade.php`.
- `components/`: `guardian-selector.blade.php`, `international-country-select.blade.php`, `international-nationality-select.blade.php`, `international-phone-input.blade.php`, `teacher-table.blade.php`.
- `Errors/`: `403.blade.php`, `404.blade.php`, `419.blade.php`, `500.blade.php`. `Pdf/`: `PdfInvoice.blade.php`, `PdfReceipt.blade.php`, `PdfReportCard.blade.php`, `PdfTranscript.blade.php`.
- `Branches/`: `BranchSummary.blade.php`, `BranchesForm.blade.php`, `BranchesIndex.blade.php`. `Messages/`: `Create.blade.php`, `Index.blade.php`, `Show.blade.php`. `SuperAdmin/`: `SuperAdminRolesCreate.blade.php`, `SuperAdminRolesEdit.blade.php`, `SuperAdminRolesIndex.blade.php`.
- `Payments/`: `PayHereCheckout.blade.php`, `PayHereRedirect.blade.php`. `Account/`: `ApiTokensIndex.blade.php`. `Registrar/`: `RegistrarDashboard.blade.php`. `emails/`: `daily-absentee-digest.blade.php`. Root: `welcome.blade.php`.

`resources/js/app.js` imports `resources/js/bootstrap.js`, which configures Axios and its XMLHttpRequest header. `resources/css/app.css` is the Tailwind CSS v4/Vite source. `public/js/app.js` adds browser behavior for theme persistence, live dates, GET-filter submission, mobile sidebar, country/phone/nationality selectors, and guardian selection. `public/css/glass.css` contains theme tokens, responsive shell, cards and component styling. `LayoutApp` loads the public JS/CSS; `welcome.blade.php` is the only inspected `@vite` consumer, so don't assume the main application shell is served from Vite output.

The standalone `public/js/app.js` is an IIFE. Its named helpers are `setTheme(t)`, `refreshLiveDates()`, `submitFilter()`, `saveSidebarScroll()`, `toggle(open)`, `configurePhoneLength(...)`, `validatePhoneLength(...)`, `updateSelection()`, `validateDocument()`, `enhanceSearchableSelect(select)` (with nested `renderOptions()` and `syncSelection()`), `updateTemplateLink()`, `validateLetters()`, and `updateMinimum()`. They handle theme/local storage, date formatting, debounced validated filters, responsive navigation, country-aware phone/document validation, guardian and searchable-select state, import templates, letter-only fields and date ranges. Anonymous event handlers connect these helpers to form, sidebar, dropdown, visibility, mutation and flash-message events.

`resources/js/app.js` defines no application function; it imports `./bootstrap`. `resources/js/bootstrap.js` exposes Axios as `window.axios` and sets `X-Requested-With`. Inline Blade scripts additionally define `wireScopeToggle(prefix)` (fee structure scope), nested `sync()` (fee inputs), `updateTotal()` (invoice totals), and `toggleStudentLoginFields()` (student form); other templates use page-specific anonymous handlers for charts, marks, timetable, invoice/payment, grade-scale and procurement behavior.

Blade files mainly render screens, forms, and partials; they are not reusable named PHP functions. `resources/views/components/guardian-selector.blade.php`, `international-country-select.blade.php`, `international-nationality-select.blade.php`, and `international-phone-input.blade.php` are reusable anonymous Blade components; `teacher-table.blade.php` is a reusable table partial. `resources/views/Layouts/` contains the common shell and shared partials.

#### Configuration, bootstrap, and operational files

- `config/app.php` — app identity/locale/timezone; `auth.php` — guards/providers; `cache.php` — cache stores; `database.php` — database connections; `dompdf.php` — PDF settings.
- `config/filesystems.php` — file disks; `logging.php` — log channels; `mail.php` — mail transports; `payroll.php` — payroll settings; `permission.php` — RBAC package settings.
- `config/queue.php` — queues/failed jobs; `sanctum.php` — API tokens; `school.php` — school currencies/document rules/domain defaults; `services.php` — integration settings; `session.php` — session/cookie configuration.
- `bootstrap/app.php` builds the Laravel app and registers routes/middleware/exception behavior; `bootstrap/providers.php` lists `AppServiceProvider` and `BranchIsolationServiceProvider`; `bootstrap/cache/` is generated framework state.
- `deploy/README.md` — deployment/backup reference; `deploy/nginx-sms.conf` — Nginx HTTPS virtual host; `deploy/sms-queue.service` — systemd queue worker; `deploy/sms-cron` — schedule/backup cron; `deploy/backup.sh` — database/storage archive script. The fuller runbook is `docs/guides/deploy.md`.
- Web root support files: `public/index.php` Laravel entry point, `public/.htaccess` rewrite rules, `public/robots.txt`, `public/favicon.ico`, `public/hot` Vite development marker, and `public/css/`/`public/js/` authored assets. `public/build/` is generated; `public/vendor/` contains third-party assets.
- `storage/` contains runtime uploads, PDFs, logs, sessions, cache and compiled Blade files; treat them as mutable, private environment data, not source.

#### Project docs

Root documentation and handover files:

`README.md`, `README-SETUP.md`, `IGK_ERP_TEAM_MODULE_OWNERSHIP_MATRIX.md`, `IGK_ERP_TEAM_SETUP_AND_DEVELOPER_GUIDE.md`, `IGK_SCHOOL_ERP_MANUAL_FUNCTIONAL_TEST_CHECKLIST.md`, `IGK_SCHOOL_ERP_MANUAL_TEST_DATABASE_SAFETY_REVIEW.md`, `IGK_SCHOOL_ERP_MANUAL_TEST_DATABASE_SETUP_PLAN.md`, `IGK_SCHOOL_ERP_MANUAL_TEST_ENVIRONMENT_PREPARATION_GUIDE.md`, `IGK_SCHOOL_ERP_SEEDER_RECOVERY_PLAN.md`, `sample dataset.md`, `sms-49day-laravel-sop.html`, `MANIFEST.txt`, and this guide.

`docs/`:

- Root: `architecture.md` (architecture/service invariants); `international-school-sop-coverage.md` (SOP coverage); `known-issues.md` (known gaps); `sop.md` (workflow SOP); `IGK_ERP_COMPLETE_SYSTEM_DOCUMENTATION.html` and `.pdf` (system reference documents).
- `database/schema.sql` — SQL schema reference.
- `decisions/ADR-001-no-repository-layer.md`, `ADR-002-money-as-decimal-strings.md`, `ADR-003-console-commands.md`, `ADR-004-accounts-numbering-and-reversals.md` — architecture decisions.
- `guides/admin.md`, `accountant.md`, `teacher.md`, `student.md` — role/user guides; `install.md`, `environment.md`, `deploy.md`, `backup-restore.md` — operations guides.
- `testing/finance-test-case-register.md`, `finance-gap-closure-report.md` — finance verification material.
- `archive/README.md`, `IGK_ERP_BUSINESS_PROPOSAL_TRACEABILITY_MATRIX.md`, `IGK_ERP_DEMO_LOGIN_FIX_REPORT.md`, `IGK_ERP_ROLE_AND_LOGIN_VERIFICATION_REPORT.md`, `IGK_ERP_SYSTEM_ARCHITECTURE_AND_WORKFLOW.md`, `IGK_SCHOOL_ERP_COMPLETION_REPORT.md`, `IGK_SCHOOL_ERP_FINAL_AUDIT_REPORT.md`, `IGK_SCHOOL_ERP_PENDING_TASKS_VERIFICATION_PLAN.md`, `IGK_SCHOOL_ERP_PROGRESS_REPORT.md` — historical material; check dates and current code before treating it as present behavior.

Other root project/build files: `.editorconfig` (editor formatting), `.env.example` (MySQL local template with `school_management_system_testing_db`), `.gitattributes` (Git text handling), `.gitignore` (local/generated exclusions), `artisan` (CLI entry), `composer.json`/`composer.lock` (PHP dependencies/scripts), `package.json`/`package-lock.json` (frontend dependencies/scripts), `phpstan.neon` (static-analysis config), `phpunit.xml` (test environment/suites), `pint.json` (formatter config), `setup-team.bat` (Windows local setup), and `vite.config.js` (frontend build inputs). `.env` is per-developer private configuration and must never be copied into a presentation or team archive; `.phpunit.result.cache` is generated.

`bin/` contains these Composer tool launchers and their Windows wrappers (developer tooling, not school-business logic): `carbon`, `carbon.bat`, `patch-type-declarations`, `patch-type-declarations.bat`, `php-parse`, `php-parse.bat`, `phpunit`, `phpunit.bat`, `pint`, `pint.bat`, `psysh`, `psysh.bat`, `sail`, `sail.bat`, `var-dump-server`, `var-dump-server.bat`, `yaml-lint`, `yaml-lint.bat`.

#### Build/runtime outline

`composer install` installs locked PHP dependencies; `npm ci` installs locked frontend dependencies; `npm run build` invokes Vite (`vite.config.js`) to compile `resources/css/app.css` and `resources/js/app.js`. `setup-team.bat` automates these steps and initializes a missing local `.env`, but does not migrate or seed the database. `artisan` provides Laravel CLI; `phpunit.xml` selects in-memory SQLite for automated tests. Deployment support is separate from local setup and should follow the deploy runbook, not the demo seeder workflow.

## 7. End-to-end workflows to demonstrate

When presenting, use a demo account and safe sample data. Do not create real personal/student data for a public presentation.

1. **Sign in and role access:** show the role's landing page and explain that route/middleware/policy checks govern access.
2. **Admin setup:** show institution/branch and academic reference data as allowed by the demo role.
3. **Student journey:** show a student record, guardian relationship, and class/enrollment context.
4. **Teaching/academics:** show a teacher's assigned class, attendance or assessment, and an authorized result/report view.
5. **Fee/payment:** show fee configuration, a demo invoice, a permitted payment, and its receipt/report; never imply payment credentials are active.
6. **Operations and support modules:** show only modules present and seeded in the running build (e.g. library, inventory, transport, LMS).
7. **Reports and audit/access behavior:** show an available report/PDF and explain a denied action using a test role, without exposing another person's data.

For each step state: **actor → screen/action → validation/access rule → database effect → visible result**. If the running demo does not contain a feature, call it a roadmap item rather than simulating it as complete.

## 8. Suggested presentation outline (10–15 minutes)

| Slide | Content | Suggested speaker notes |
|---|---|---|
| 1 | Title and team | Project name, team members, date, and demo environment. |
| 2 | Problem and objective | Which school workflows the ERP brings together. |
| 3 | Users and access | Explain the eight demo roles and role/permission-based views. |
| 4 | Architecture | Browser → Laravel routes/middleware → controllers/services/models → database → Blade/PDF response. |
| 5 | Student lifecycle | Student/guardian → enrollment → class/teacher → attendance/results. |
| 6 | Finance lifecycle | Fee structure/items → invoice → payment/receipt → finance report. |
| 7 | Other modules | Only verified features in the current build; label future work clearly. |
| 8 | Data safety | Local demo seeders, separate developer DBs, no shared `.env`, test DB isolation. |
| 9 | Team ownership | Core, Accounts, and Post-MVP responsibility matrix. |
| 10 | Quality and next steps | Tests, access checks, known gaps, proposed next milestone. |
| 11 | Live demo / Q&A | Use local synthetic demo accounts/data; avoid real data and production credentials. |

### Speaker script template

> “IGK School ERP is a role-based school operations application. A user signs in, and the application routes the request through authentication and access checks before a controller validates and processes it. The resulting records are stored through the domain models and database; the user receives a page, report, or other permitted response. We will demonstrate the student/academic/finance journey using local synthetic demo data. Each teammate owns a defined module, while shared files are coordinated through reviewed branches and pull requests. Features marked Post-MVP are roadmap items unless we verify them in the running build.”

Adjust the script to the actual live system; do not claim a workflow is complete solely because it appears in the team allocation.

**தமிழ் பேசும் script:** “IGK School ERP என்பது பள்ளி நிர்வாகத்திற்கான role-based web system. ஒவ்வொரு user-க்கும் அவருடைய role மற்றும் permission-க்கு ஏற்ற pages/actions மட்டுமே அனுமதிக்கப்படுகின்றன. ஒரு request route மற்றும் middleware வழியாக access check செய்யப்பட்டு, validation பின் controller/service logic மூலம் database-ல் சேமிக்கப்படுகிறது. இன்று synthetic demo data கொண்டு student → academics → fees/payments → reports flow-ஐக் காட்டுகிறோம். ஒவ்வொரு teammate-க்கும் தனி module ownership உள்ளது; shared files PR review மற்றும் coordination வழியாக மாற்றப்படுகின்றன. Post-MVP என்று குறிக்கப்பட்டவை தற்போதைய build-ல் உண்மையாக verify செய்யப்படாவிட்டால் roadmap items ஆகவே கூறப்பட வேண்டும்.”

## 9. Prompt for Claude or another slide/report assistant

Copy this document into the assistant only after confirming that it contains no local secrets. Ask it to generate Tamil slides/report (keep technical identifiers in English):

> Create a clear Tamil-language presentation and a short written report for a school ERP project audience from this guide. Keep code identifiers, class names, file paths, and standard technical terms in English. Use the actual architecture, role names, verified workflow descriptions, and team ownership table. Organize slides as: project objective, users/roles, architecture, end-to-end student journey, finance journey, other verified modules, data safety/testing, team split, roadmap, conclusion/Q&A. Separate implemented behavior from Post-MVP plans. Do not invent features, metrics, screenshots, integrations, or security claims. Flag any uncertainty for the presenter to verify. Use concise slide text and place fuller explanations in Tamil speaker notes. Keep demo data synthetic and omit passwords, keys, `.env` contents, private database settings, logs, and personal information.

## 10. Verification and safe-use checklist

- **Route activation:** `routes/api.php`, `routes/accounting.php`, and `routes/admin.php` exist but were not included by inspected bootstrap/web registration; confirm with `php artisan route:list` before demonstrating their endpoints.
- **Middleware/listener wiring:** `SetCurrentAcademicContext` is present but not appended to the inspected web group; event listener auto-discovery and scheduled-job runtime behavior were not verified.
- **Source inconsistencies to review before technical claims:** invoice number format differs between `InvoiceService` and `InvoiceNumberGenerator`; enum and exception names are repeated in aggregate and standalone files.
- **Historical docs:** manual DB safety/recovery notes may describe an earlier schema/seeder snapshot. Prefer current migrations, tests and runtime output where they disagree.
- Use local synthetic accounts/data for demos; do not expose `.env`, actual student details, database dumps, logs, API keys, or passwords.
- Confirm each feature in the current running app or a relevant test before describing it as working.
- Keep each teammate's `.env` and database separate; seed locally from the committed seeders.
- Use `php artisan test`, `php vendor\bin\pint --test`, and `npm run build` as applicable.
- Review current `docs/known-issues.md` and the relevant manual test/safety documents before public claims.
- Refresh this guide when routes, modules, ownership, seeders, or major request flows change.
