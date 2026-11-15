# Core Shared Architecture & Cross-Cutting Foundation

**Shared Ownership:** Shared by all 5 engineers (Alass, Sumaiya, Rajiya, Hasfa, Shilfa)  
**Deliverable Zip File:** `CORE_SHARED_SYSTEM_PACKAGE.zip` (located in `docs/TEAM/packages/` and `documents/TEAM/packages/`)  

---

### 1. Architectural Philosophy & Invariants

The Core Shared Architecture provides the foundational bedrock for the IGK School ERP. It ensures that every domain module (Admin, Teacher, Student, Reports, Finance) operates consistently without duplicating base infrastructure:

1. **Multi-Tenant Campus Isolation (`BranchOwned`):**
   - Implemented as an Eloquent Global Scope in `app/Models/Scopes/BranchOwned.php`.
   - Automatically scopes database queries based on the authenticated user's `branch_id`.
   - Global Administrators (Super Admin, Institution Admin) and Authorized Finance Officers (Accountants) bypass single-branch restrictions to access "All Campuses" via `BranchContext::canAccessAllBranches()`.
2. **Glassmorphic Design System & Night-Mode Immune Physical Printing:**
   - Powered by `public/css/glass.css` with dark/light theme switching remembered in `localStorage['igk-theme']`.
   - All printable outputs (A4 Report Cards, Payslips, Invoices, Receipts) utilize `@media print` rules enforcing white backgrounds, crisp dark typography, and zero translucent blur artifacts.
3. **Role-Based Access Control (RBAC) Hierarchy:**
   - Powered by Spatie Laravel Permission with strictly defined guards and permissions mapped in `database/seeders/RolePermissionSeeder.php`.
   - User model (`app/Models/User.php`) includes standardized role check helpers:
     * `isSuperAdmin()`
     * `isInstitutionAdmin()`
     * `isTeacher()`
     * `isStudent()`
     * `isAccountant()`
     * `isParent()`
     * `dashboardRoute()` (maps authenticated role to respective portal home).

---

### 2. Complete File Inventory of Shared Files

#### Master Routes & Entry Points:
* `routes/web.php` (Root router dispatching to module route groups)
* `routes/api.php` (API endpoints for biometric events and student data)
* `routes/console.php` (Scheduled tasks and artisan commands)

#### Core Controllers & Models:
* `app/Http/Controllers/Controller.php` (Base Laravel controller)
* `app/Models/User.php` (Core authenticatable user entity)
* `app/Models/Scopes/BranchOwned.php` (Multi-branch tenant scoping global scope)

#### Shared Views & Layouts:
* `resources/views/Layouts/LayoutApp.blade.php` (Master authenticated portal shell, sidebar navigation, topbar campus switcher, notifications dropdown)
* `resources/views/Layouts/LayoutHead.blade.php` (Pre-paint theme loader, CSS dependencies, meta tags)
* `resources/views/Layouts/LayoutErrors.blade.php` (Standardized validation error alerts)
* `resources/views/Layouts/LayoutStat.blade.php` (Standardized metric card widget)
* `resources/views/Layouts/LayoutAttendanceTable.blade.php` (Reusable attendance grid)
* `resources/views/Layouts/LayoutResultsTable.blade.php` (Reusable marks/grades table)
* `resources/views/Errors/` (403 Forbidden, 404 Not Found, 419 Page Expired, 500 Server Error)
* `resources/views/emails/` (Absentee notice email templates)

#### Assets & Frontend Tooling:
* `public/css/glass.css` (Glassmorphic stylesheet)
* `public/js/app.js` (Theme toggle, live dates, dynamic UI helpers)
* `public/vendor/` (Vendored offline Bootstrap 5, Bootstrap Icons, Flag Icons, Inter font)
* `vite.config.js` (Vite 7 frontend bundle configuration)
* `package.json` (Frontend dependencies)

#### Database Foundations:
* `database/migrations/` (All 61 structural database schema migrations)
* `database/seeders/` (DatabaseSeeder, RolePermissionSeeder, UserSeeder, BranchSeeder, DemoSeeder)

#### Environment & Tooling:
* `composer.json` (PHP 8.3 dependencies)
* `phpunit.xml` (Automated testing configuration with SQLite in-memory runner)
* `setup-team.bat` (Automated 1-click Windows setup script)

---

### 3. Rules for Modifying Shared Files

To prevent git merge conflicts in Fork:
* **`routes/web.php`:** Never add feature logic directly to `routes/web.php`. Register only module prefix routes that point to your respective domain file (`routes/teacher.php`, `routes/accountant.php`, etc.).
* **`LayoutApp.blade.php`:** Sidebar navigation arrays are strictly segregated by role key (`'admin'`, `'teacher'`, `'student'`, `'accountant'`, `'guardian'`, `'registrar'`). Edit only your own role's array items.
* **Database Migrations:** Never modify an already-committed migration file. If a new column is needed, create a new timestamped migration.
