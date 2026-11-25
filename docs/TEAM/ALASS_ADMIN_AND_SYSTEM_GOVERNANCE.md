# Team Member: ALASS (Team Leader)
## Domain: Super Admin, Multi-Campus Architecture & System Governance

**Assigned Engineer:** Alass  
**System Role:** Super Admin / Institution Admin / System Architect  
**Deliverable Zip File:** `ALASS_ADMIN_PACKAGE.zip` (located in `G:\ALL DOCS\packages\` and `G:\ALL DOCS\`)  

---

### 1. Functional Scope & Key Responsibilities

As Team Leader and System Governance owner, Alass owns the administrative backbone, security, multi-campus scoping, and system auditability:
1. **Multi-Campus Branch Network Management & Registrar Visibility:**
   - Multi-campus branch switching (All Campuses vs Main Campus vs Kandy Campus).
   - Global multi-tenant scoping via `BranchContext` and `BranchOwned` architectural scopes.
   - Comprehensive multi-campus access for Academic Registrars (`User::isRegistrar()`) ensuring full visibility of teachers, students, and class allocations across all branches.
   - Global Auto-Sort by Latest Activity (`updated_at DESC`, `id DESC`) across all operational modules, moving newly created or modified records to the top immediately.
2. **Users Directory & RBAC Security (`/admin/users`):**
   - Multi-relational keyword search across Name, Email, and Phone number (resolved across User, Teacher, Student, and Guardian models).
   - Complete System Role inclusion (including restored Student role) and dynamic filtering by System Role, Campus/Branch, and Account Status.
   - Live matching totals counter and pagination preservation (`withQueryString()`).
   - Two-Factor Authentication (2FA) enforcement via Google Authenticator with visual SVG QR Code pairing, mandatory 6-digit TOTP verification on every admin login, and device trust disabled.
   - Administrator Forgot Password via 6-digit Email OTP + Gmail SMTP (`PasswordResetOtpService`), strictly scoped to Super Admin and Institution Admin accounts while preserving 2FA.
   - User Management branch assignment supporting **`All Branches / Global (Admin only)`** or specific campus selection for Super Admin & Institution Admin.
3. **Human Resources & Statutory EPF/ETF Payroll (`/admin/payroll`):**
   - Complete HR Employees directory (`EmployeeSyncService`) auto-syncing all Teachers and Staff Users with **Category-Wise Tabs & Filters** (`Teaching Faculty`, `Administration`, `Finance & Accounts`, `Admissions & Registrar`, `Library & Operations`).
   - Bidirectional record synchronization across `User`, `Teacher`, `Employee`, `Student`, and `StaffAttendance` updates.
   - Employee master records management and leave approvals.
   - **Payroll Month** selector (`YYYY-MM`) auto-snapping `period_start` and `period_end` to a complete calendar month.
   - Staff & Employee personal attendance self-service (`/my-attendance`, `/teacher/profile`) with working hours, check-in/out, and monthly KPI metrics.
   - Statutory payroll computation engine (EPF 8% employee, 12% employer; ETF 3%; APIT income tax).
   - Printable night-mode immune official payslips with pixel-perfect A4 printing.
4. **Super Admin Salary Deductions Governance (`/admin/salary-deductions`):**
   - Isolated governance console for Super Admin to manage custom salary deductions.
   - Dual-mode deduction calculation (Automatic attendance unpaid leave/absent vs Manual wish amounts).
5. **System Audit Trail & Security Forensic Log (`/admin/audit-logs`):**
   - Append-only immutable security log tracking all creates, updates, deletes, and payment reversals.
6. **Campus Communication & Multi-Role Private Messaging (`/messages`):**
   - Categorized recipient composer (Administrative Staff, Faculty, Students, Guardians).
   - Real-time search filtering, unread counters, and recipient isolation.
7. **Role & Permission Safeguards & Academic Validation:**
   - Hardened `RoleController` and `RolePolicy` safeguarding built-in core roles (Super Administrator, Institution Administrator, Teacher, Student, Guardian, Accountant) from unauthorized deletion or tampering.
   - Added `TermRequest` validating date bounds and sequential ordering across academic terms.
   - Multi-branch class name uniqueness scoping (`scope_class_name_uniqueness_to_branch`) allowing standard grade/class names across branches.
8. **Global Dropdown UI & Verification:**
   - Shared `public/js/app.js` / `public/css/glass.css` behavior enforces downward `bottom-start` placement and field-aligned searchable controls across authenticated layouts.
   - The database-free regression passed 10 Chromium cases across five viewports and both themes. This is component-level evidence only; every administrative grid, form, and authenticated page has not been verified. See [Global Dropdown UI](../guides/global-dropdown-ui.md).
9. **User Management Validation & Student Profile Integrity (`BUG-026`):**
   - Hardened `StoreUserRequest` and `UpdateUserRequest` with human-friendly attribute mappings (`roles.0` -> `role`) and descriptive guidance messages.
   - Cleanly insulated generic user creation from student account enrollment, ensuring student profiles are always linked through Admissions while preserving role filtering on the users index.
10. **Two-Factor Authentication (2FA) Governance & Client Onboarding (`BUG-030`):**
   - Introduced `TWO_FACTOR_ENABLED` toggle in `config/school.php` and `app/Models/User.php` (`requiresTwoFactor()`), allowing environments to enforce strict RFC 6238 TOTP Google Authenticator 2FA or toggle it off via `.env` (`TWO_FACTOR_ENABLED=false`) for direct credentials login.
   - Provided automated database reset script (`scratch/execute_reset_2fa.php`) clearing existing 2FA secrets for instant, clean client onboarding with fresh setup keys.
11. **Table Actions & Fluid Navigation Layout (`BUG-029`):**
   - Overhauled table actions across all administration grids with `.table-actions` and `.teacher-actions` flexbox containers (`min-width: 220px`), eliminating clipping and truncation under horizontal scrolling.
   - Implemented page-level overflow clipping (`overflow-x: clip; max-width: 100vw;`) in `glass.css`, ensuring the sticky `.topbar` remains fully anchored and unclipped. Verified by `NavigationAndTableActionsUiTest`.

---

### 2. Complete File Inventory (74 Files)

#### Routes:
* `routes/modules/admin.php`
* `routes/admin.php`

#### Controllers:
* `app/Http/Controllers/InstitutionAdmin/DashboardController.php`
* `app/Http/Controllers/InstitutionAdmin/UserController.php`
* `app/Http/Controllers/InstitutionAdmin/BranchController.php`
* `app/Http/Controllers/InstitutionAdmin/SettingController.php`
* `app/Http/Controllers/InstitutionAdmin/AuditLogController.php`
* `app/Http/Controllers/InstitutionAdmin/AnnouncementController.php`
* `app/Http/Controllers/InstitutionAdmin/BulkImportController.php`
* `app/Http/Controllers/InstitutionAdmin/HrPayrollController.php`
* `app/Http/Controllers/SuperAdmin/RoleController.php`
* `app/Http/Controllers/SuperAdmin/SalaryDeductionController.php`
* `app/Http/Controllers/Auth/LoginController.php`
* `app/Http/Controllers/Auth/TwoFactorController.php`
* `app/Http/Controllers/Auth/ForgotPasswordController.php`
* `app/Http/Controllers/Auth/ResetPasswordController.php`
* `app/Http/Controllers/Auth/PasswordChangeController.php`
* `app/Http/Controllers/MessageController.php`
* `app/Http/Controllers/NotificationController.php`
* `app/Http/Controllers/ApiTokenController.php`

#### Requests & Rules:
* `app/Http/Requests/InstitutionAdmin/BulkImportRequest.php`
* `app/Http/Requests/InstitutionAdmin/TermRequest.php`
* `app/Http/Requests/SuperAdmin/UpdateRoleRequest.php`
* `app/Rules/ValidInternationalPhone.php`

#### Domain Services & Support:
* `app/Services/AnnouncementDeliveryService.php`
* `app/Services/BulkCsvImportService.php`
* `app/Services/HrPayrollService.php`
* `app/Services/SriLankaPayrollCalculator.php`
* `app/Services/BranchTransferService.php`
* `app/Support/AuditLogger.php`
* `app/Support/BranchContext.php`
* `app/Support/InternationalPhoneNumber.php`

#### Notifications:
* `app/Notifications/AnnouncementPublished.php`
* `app/Notifications/SchoolMessageReceived.php`

#### Models:
* `app/Models/Branch.php`
* `app/Models/BranchTransfer.php`
* `app/Models/Setting.php`
* `app/Models/AuditLog.php`
* `app/Models/Announcement.php`
* `app/Models/Employee.php`
* `app/Models/LeaveRequest.php`
* `app/Models/PayrollRun.php`
* `app/Models/PayrollEntry.php`
* `app/Models/PayrollDeduction.php`
* `app/Models/SchoolMessage.php`
* `app/Models/MessageConversation.php`

#### Blade Views:
* `resources/views/SuperAdmin/` (Roles, Permissions, Salary Deductions)
* `resources/views/Branches/` (Campus Form, Campus Directory, Branch Summary)
* `resources/views/Auth/` (Glassmorphic Login, Two-Factor Challenge, Two-Factor Setup, Password Reset)
* `resources/views/Messages/` (Index, Show, Create with categorized recipient modal)
* `resources/views/InstitutionAdmin/InstitutionAdminAuditLogsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminUsersIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminUsersCreate.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminUsersEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminSettingsIndex.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminAnnouncementsIndex.blade.php`
* `resources/views/InstitutionAdmin\InstitutionAdminAnnouncementsEdit.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminImportsIndex.blade.php`
* `resources/views/InstitutionAdmin/HrEmployeesIndex.blade.php`
* `resources/views/InstitutionAdmin/HrEmployeeForm.blade.php`
* `resources/views/InstitutionAdmin/HrLeavesIndex.blade.php`
* `resources/views/InstitutionAdmin/HrPayrollIndex.blade.php`
* `resources/views/InstitutionAdmin/HrPayrollShow.blade.php`
* `resources/views/InstitutionAdmin/HrPayrollPayslip.blade.php`
* `resources/views/InstitutionAdmin/InstitutionAdminDashboard.blade.php`

#### Feature Test Suites:
* `tests/Feature/RegistrarAccessTest.php`
* `tests/Feature/SingleNotificationBannerTest.php`
* `tests/Feature/SuperAdminAllCampusesTest.php`
* `tests/Feature/InternationalMultiBranchAndPayrollTest.php`
* `tests/Feature/RoleLoginVerificationTest.php`
* `tests/Feature/PasswordResetSecurityTest.php`
* `tests/Feature/PrivateMessagePrivacyAndScopingTest.php`
* `tests/Feature/PrivateMessageInputValidationTest.php`
* `tests/Feature/AuditObserverTest.php`
* `tests/Feature/InternationalCsvValidationTest.php`

---

### 3. Step-by-Step Verification Commands for Alass

```powershell
# Run all governance and multi-campus tests
php vendor/bin/phpunit tests/Feature/SuperAdminAllCampusesTest.php tests/Feature/InternationalMultiBranchAndPayrollTest.php tests/Feature/PrivateMessagePrivacyAndScopingTest.php

# Test Users Search and Filter
php artisan test --filter=RoleLoginVerificationTest
```
