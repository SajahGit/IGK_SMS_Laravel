# International Standard Multi-Branch School Management System (IGK School ERP)

[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![Bootstrap 5 Glassmorphism](https://img.shields.io/badge/UI-Glassmorphism%20Dark%2FPrint-0ea5e9?style=for-the-badge)](https://getbootstrap.com)
[![Sri Lanka Statutory Compliant](https://img.shields.io/badge/Payroll-EPF%20%7C%20ETF%20%7C%20APIT-10b981?style=for-the-badge)](https://ird.gov.lk)

An enterprise-grade, international-standard School Management and Enterprise Resource Planning (ERP) platform architected for multi-branch campuses, rigorous academic curricula (Cambridge CAIE, Edexcel, IB, AP, National), statutory HR & Payroll, fee billing, and real-time stakeholder self-service.

---

## 🌟 Key System Capabilities

### 1. Multi-Branch Campus Architecture
- **Campus Isolation & Scoping**: Enforces strict `BranchOwned` multi-tenancy across academic sessions, classes, admissions, and payroll.
- **Student & Teacher Branch Assignment**: Teachers and students are assigned to physical campuses with complete transfer audit logging (`BranchTransfer` model and `BranchTransferService`).
- **Campus-Prefixed Admission Numbers**: Dynamic admission numbering with campus prefix (e.g., `COL/2026/0001`, `KAN/2026/0002`).
- **Registrar Inter-Branch Navigation**: Registrars and Administrators can switch campus contexts with reactive cascading dropdowns.

### 2. Dual-Mode Salary Deduction Architecture
- **Automatic Attendance Deductions**:
  - Automatically assesses employee attendance records and approved unpaid leave requests (`LeaveRequest`).
  - **Super Admin Configurable Policy**: Super Admin can switch between dynamic pro-rata daily salary (`base_salary / 30 * unpaid_days`) or a **custom fixed amount** (e.g., Rs. 2,000 / day). Super Admin can modify this policy anytime.
  - Automatically generates itemized `PayrollDeduction` records with full calculation notes.
- **Manual Custom / Wished Amount Deductions**:
  - Super Admin can record custom one-off or recurring deductions for any staff member (advances, emergency loans, property damages, disciplinary fines).
  - Automatically links to the employee's next payroll entry upon generation.
- **Dedicated Super Admin Exclusivity**:
  - Salary Deductions are separated from standard HR payroll generation, maintaining strict audit governance and preventing unauthorized deductions by branch-level accountants.
- **Connected Staff Transparency**:
  - Connected teachers and employees can view their full breakdown of automatic attendance deductions and manual deductions directly in their personal profile portal (`/teacher/profile`).

### 3. HR & Statutory Payroll Engine
- **Sri Lanka Statutory Compliance**:
  - **EPF**: 8% Employee contribution, 12% Employer contribution.
  - **ETF**: 3% Employer contribution.
  - **APIT (PAYE)**: Progressive monthly tax brackets (0% up to Rs. 100k, 6% to 36% progressive).
- **Night-Mode Immune & A4 Portrait Payslips**:
  - Scoped `.payslip-sheet` container guarantees crystal-clear contrast (#0f172a typography on crisp white backing) even when the system is set to dark mode (`data-bs-theme="dark"`).
  - Zero white-text-on-white collisions.
  - Clean `@page { size: A4 portrait; }` physical print styling, hiding sidebars, headers, and buttons for official sealed printouts.

### 4. International Curriculum & Academic Reports
- **Multi-Curriculum Support**: Cambridge CAIE (Checkpoint, IGCSE, A-Level), Edexcel, International Baccalaureate (IB PYP/MYP/DP), Advanced Placement (AP), and National curricula.
- **Report R8 (Subject Academic Performance)**:
  - Weighted subject averages, pass rates, highest/lowest scores, and multi-tier grade distribution (IB 7-point, Alpha A*-U, GCSE 9-1, Early Years descriptors).
- **Dynamic Cascading Class & Section Pickers**:
  - Registrar and Admin forms load available sections and subjects dynamically upon class selection, preventing orphan registrations.

### 5. Teacher Self-Service Portal (`/teacher/profile`)
- **Profile Overview**: Contact info, department, designation, and assigned branch campus.
- **Academic Timetable**: Weekly scheduled periods, classrooms, and assigned subjects.
- **Leave History**: Total allowed, utilized, pending, and approved leaves.
- **Financial Transparency**: Live access to generated monthly payslips with detailed earnings and dual-mode deductions breakdown.

---

## 🏗️ Technical Stack & Architecture

- **Backend**: Laravel 12.x, PHP 8.3
- **Database**: MySQL 8.0+ (Production) / SQLite in-memory (Automated Tests)
- **Frontend**: Blade, Tailwind CSS / Vite, Vanilla JavaScript, Bootstrap 5 (Glassmorphic Dark Theme)
- **PDF Generation**: DomPDF with scoped document stylesheets
- **Security & Authorization**: Spatie Laravel Permissions + Custom Policies + Multi-Branch Global Scopes

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── SuperAdmin/SalaryDeductionController.php   # Dedicated Super Admin Deduction Console
│   │   ├── Teacher/ProfileController.php             # Teacher Self-Service Portal
│   │   ├── InstitutionAdmin/HrPayrollController.php  # HR Payroll Generation & Payslip View
│   │   └── Report/EnrollmentRegisterReportController.php
│   └── Requests/                                     # Form Request Validation
├── Models/
│   ├── BranchTransfer.php                            # Student & Teacher Branch Transfer History
│   ├── PayrollDeduction.php                          # Itemized Auto & Manual Deductions
│   ├── PayrollEntry.php                              # Employee Monthly Payslip Records
│   ├── Teacher.php                                   # Branch-assigned Faculty Entity
│   └── Student.php                                   # Branch-assigned Student Entity
├── Services/
│   ├── BranchTransferService.php                     # Campus Transfer Workflows
│   ├── HrPayrollService.php                          # Statutory EPF/ETF/APIT & Dual-Deduction Engine
│   └── EnrollmentService.php                         # Campus Admission & Enrollment
```

---

## 🚀 Quick Start Guide

### Prerequisites
- PHP 8.3+ with `pdo`, `mbstring`, `bcmath`, `curl`, `intl` extensions
- Composer 2.x
- Node.js 20+ & NPM

### Setup Instructions
1. **Clone the Repository & Install Dependencies**:
   ```bash
   git clone <repo-url>
   cd IGK_SMS_Laravel
   composer install
   npm install
   ```

2. **Configure Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Migration & Seeding**:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Compile Assets**:
   ```bash
   npm run build
   ```

5. **Start Development Server**:
   ```bash
   php artisan serve
   ```

---

## 🧪 Testing & Quality Assurance

Run the automated test suites covering multi-branch transfers, dual-mode deductions, payslip rendering, and registrar access:

```bash
# Multi-branch, Teacher Assignment, Dual Deductions & Payslip Night-Mode Tests
php vendor/bin/phpunit tests/Feature/InternationalMultiBranchAndPayrollTest.php

# Registrar Dynamic Access & Cascading Dropdowns
php vendor/bin/phpunit tests/Feature/RegistrarAccessTest.php

# Academic Reports & R8 Performance Parity Tests
php vendor/bin/phpunit tests/Feature/ReportsParityTest.php
```

---

## 👥 Engineering Team Workstream & Git Fork Collaboration

The system is architected into 5 specialized engineering domains, complete with individual standalone zip deployment packages hosted outside the code repository in `G:\ALL DOCS\packages\` (and `G:\ALL DOCS\`):

* **Alass (Team Leader)**: Super Admin, Multi-Campus Branch Switching, RBAC Security, HR & EPF/ETF Payroll, Salary Deductions Governance & Private Messaging &rarr; [`docs/TEAM/ALASS_ADMIN_AND_SYSTEM_GOVERNANCE.md`](./docs/TEAM/ALASS_ADMIN_AND_SYSTEM_GOVERNANCE.md)
* **Sumaiya**: Teacher Desk, Daily Attendance Roll Call, Exam Schedules & Search Engine, Marks Entry Boundary Checks & Batch GPA Publishing &rarr; [`docs/TEAM/SUMAIYA_TEACHER_EXAMS_AND_ACADEMICS.md`](./docs/TEAM/SUMAIYA_TEACHER_EXAMS_AND_ACADEMICS.md)
* **Rajiya**: Student Information System (SIS), Admissions Onboarding, Multi-Child Guardian Portal, Academic Catalog, Registrar Cross-Campus Access & Transcripts &rarr; [`docs/TEAM/RAJIYA_STUDENT_ADMISSIONS_AND_GUARDIAN.md`](./docs/TEAM/RAJIYA_STUDENT_ADMISSIONS_AND_GUARDIAN.md)
* **Hasfa**: The 11 Master Reports Suite, High-Volume CSV Streaming, DomPDF Engines, Library Circulation & LMS Modules &rarr; [`docs/TEAM/HASFA_REPORTS_LIBRARY_AND_LMS.md`](./docs/TEAM/HASFA_REPORTS_LIBRARY_AND_LMS.md)
* **Shilfa**: Invoicing & Billing, Counter Cashiering & Receipts, Online PayHere Gateway, Double-Entry General Ledger & Procurement &rarr; [`docs/TEAM/SHILFA_FINANCE_ACCOUNTING_AND_PROCUREMENT.md`](./docs/TEAM/SHILFA_FINANCE_ACCOUNTING_AND_PROCUREMENT.md)
* **Core Shared Foundation**: Shared framework, base migrations, global glassmorphic layouts & `User` model &rarr; [`docs/TEAM/CORE_SHARED_ARCHITECTURE.md`](./docs/TEAM/CORE_SHARED_ARCHITECTURE.md)
* **Fork Git GUI Guide**: Step-by-step instructions to clone, switch to `develop`, extract packages, commit, and push &rarr; [`docs/TEAM/GIT_FORK_COLLABORATION_AND_SETUP_GUIDE.md`](./docs/TEAM/GIT_FORK_COLLABORATION_AND_SETUP_GUIDE.md)

---

## 📜 Documentation Index

All operational and architectural documentation files are maintained inside the [`docs/`](./docs/) directory:

- [Team Responsibility Matrix & Deliverables](./docs/TEAM/TEAM_OVERVIEW_AND_RESPONSIBILITY_MATRIX.md)
- [Git & Fork Collaboration Guide](./docs/TEAM/GIT_FORK_COLLABORATION_AND_SETUP_GUIDE.md)
- [Complete System & Code Map Presentation Guide](./docs/IGK_SCHOOL_ERP_COMPLETE_SYSTEM_AND_PRESENTATION_GUIDE.md)
- [Complete System PDF Handbook (88 Pages)](./docs/IGK_ERP_COMPLETE_SYSTEM_DOCUMENTATION.pdf)
- [Comprehensive System HTML Handbook](./docs/IGK_ERP_COMPLETE_SYSTEM_DOCUMENTATION.html)
- [System Architecture & Module Invariants](./docs/architecture.md)
- [Sample Role Logins & QA Test Dataset](./docs/sample%20dataset.md)
- [Local Developer Setup Guide](./docs/README-SETUP.md)
- [Manual Functional Test Checklist](./docs/IGK_SCHOOL_ERP_MANUAL_FUNCTIONAL_TEST_CHECKLIST.md)
- [Known Issues & Bug Register](./docs/known-issues.md)
