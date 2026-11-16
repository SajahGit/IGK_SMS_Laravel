# IGK School ERP — Team Division & Architectural Responsibility Matrix

**Institution:** International School Network (Multi-Campus)  
**Framework:** Laravel 12.x / PHP 8.3 / MySQL / Vite 7 / Bootstrap 5 / Glassmorphism UI  
**Target Git Branch:** `develop`  
**Git Client Standard:** Fork Git GUI Application (Windows)  

---

## 1. Executive Summary & New Architectural Division

The previous assignment separated roles into basic buckets. As the system expanded into multi-campus isolation, HR & statutory EPF/ETF payroll, dual-mode salary deductions, double-entry general ledger accounting, online PayHere gateway, biometrics, private multi-category messaging, and a 55+ table relational schema, the responsibilities have been **re-architected into 5 specialized engineering domains** with **100% boundary isolation**:

```
+---------------------------------------------------------------------------------------------------+
|                                      CORE SHARED FOUNDATION                                       |
|  User Identity, Authentication, Role & Permission Scoping (BranchOwned), Glassmorphic Layouts,    |
|  Bootstrap 5 Assets, Global Exceptions, Base Database Migrations, Master Seeders, System Config   |
+---------------------------------------------------------------------------------------------------+
       |                            |                     |                    |                   |
       v                            v                     v                    v                   v
+--------------+             +--------------+     +--------------+     +--------------+    +---------------+
|    ALASS     |             |   SUMAIYA    |     |    RAJIYA    |     |    HASFA     |    |    SHILFA     |
| (Team Lead)  |             |  (Teacher)   |     |  (Student)   |     |  (Reports)   |    |   (Finance)   |
|--------------|             |--------------|     |--------------|     |--------------|    |---------------|
| Super Admin  |             | Teacher Desk |     | Student SIS  |     | 11 Master    |    | Invoicing     |
| Campus Admin |             | Marks Entry  |     | Admissions   |     |   Reports    |    | Fee Structure |
| Users & RBAC |             | Timetable    |     | Guardian App |     | CSV Streamer |    | Cashier Desk  |
| HR & Payroll |             | Examinations |     | Campus Move  |     | DomPDF Suite |    | Gen. Ledger   |
| Deductions   |             | Grade Scales |     | Transcripts  |     | Library Sys. |    | Chart of Acc. |
| Audit Trail  |             | Biometrics   |     | Hostels/Bus  |     | LMS & Course |    | Procurement   |
| Messages     |             | Attendance   |     | Cafeteria    |     | Analytics    |    | PayHere Gate  |
+--------------+             +--------------+     +--------------+     +--------------+    +---------------+
```

---

## 2. High-Level Responsibility Matrix

| Member | Primary Domain | Core Engineering Ownership | Standalone Zip Package |
| :--- | :--- | :--- | :--- |
| **ALASS**<br>*(Team Leader)* | **Super Admin, Multi-Campus & System Governance** | Multi-campus branch switching, Super Admin & Campus Admin desks, Users directory & RBAC security, HR & statutory payroll processing (EPF/ETF), Super Admin salary deductions console, system settings, immutable forensic audit trail, campus announcements, and private multi-role messaging. | `ALASS_ADMIN_PACKAGE.zip` |
| **SUMAIYA** | **Teacher Portal, Examinations & Academic Grading** | Teacher desk portal, faculty self-service, class-subject allocation, automated timetable clash detector, daily attendance roll call, biometric sync endpoints, exam paper configuration, marks entry with boundary checks, institution grade scale, and term results batch GPA publishing. | `SUMAIYA_TEACHER_PACKAGE.zip` |
| **RAJIYA** | **Student Lifecycle, Admissions & Guardian Portal** | Admissions registration & document onboarding, Student master profiles & portal, multi-child Guardian portal & family context switcher, academic catalog (Departments, Programs, Curricula, Classes), student campus transfers, official transcript generation, hostel room allotment, fleet transport route assignments, and cafeteria meals. | `RAJIYA_STUDENT_PACKAGE.zip` |
| **HASFA** | **The 11 Master Reports Suite, Library & LMS** | The 11 Core Operational & Statutory Reports (Enrollment, Attendance, Results, Performance, Financial, Defaulters), real-time memory-safe CSV streaming engine, pixel-perfect DomPDF generation, Library Information System & circulation desk (catalog, loans, fines), and Learning Management System (LMS courses, modules, assignments, submissions). | `HASFA_REPORTS_PACKAGE.zip` |
| **SHILFA** | **Financial Accounting, Billing, Ledgers & Procurement** | Fee structures catalog & billing rules, cohort student invoicing engine (draft vs issued, discounts, voiding), counter cashier payments & printable official receipts, PayHere online gateway integration, double-entry general ledger (Chart of Accounts, Journal entries, Trial Balance, Ledger, Income & Expense, Branch Summary), and Procurement supply chain. | `SHILFA_FINANCE_PACKAGE.zip` |
| **ALL TEAM** | **Core Shared Architecture** | Base Laravel 12 kernel, application bootstrap, shared `LayoutApp` views, `glass.css`, `app.js`, `User` model, `BranchOwned` global scope, base migrations, and `RolePermissionSeeder`. | `CORE_SHARED_SYSTEM_PACKAGE.zip` |

---

## 3. Package Delivery Manifest

All individual zip packages have been built and placed in both:
1. `docs/TEAM/packages/`
2. `documents/TEAM/packages/`

| Package File Name | Items Count | Uncompressed Scope |
| :--- | :---: | :--- |
| `ALASS_ADMIN_PACKAGE.zip` | **71 items** | Super Admin & Admin Controllers, HR Payroll Services, Audit Observers, Messaging, Admin Blade views, Feature test suites. |
| `SUMAIYA_TEACHER_PACKAGE.zip` | **57 items** | Teacher Controllers, Marks & Grading Services, Timetable clash detector, Attendance engine, Teacher views, Feature tests. |
| `RAJIYA_STUDENT_PACKAGE.zip` | **103 items** | Student & Guardian Controllers, Admissions & Enrollment Services, Transcripts, SIS Blade views, Guardian views, Feature tests. |
| `HASFA_REPORTS_PACKAGE.zip` | **37 items** | 11 Report Controllers, CsvStreamer, Report views, Library circulation system, LMS modules, PDF report card templates, Feature tests. |
| `SHILFA_FINANCE_PACKAGE.zip` | **68 items** | Invoicing & Payment Services, Accountant Controllers, General Ledger, Chart of Accounts, Procurement, Finance views, Feature tests. |
| `CORE_SHARED_SYSTEM_PACKAGE.zip` | **20 items** | Framework configuration, database migrations, master seeders, global glassmorphic layouts, shared User model and scripts. |

---

## 4. Verification & Clean Extraction Invariant

Every package preserves the **exact relative directory path** from the root of the project:
* Extracting any package directly into the root directory of Sir's repository (`g:\IGK_SMS_Laravel`) will place every file into its required subdirectory (`app/`, `resources/views/`, `routes/`, `tests/`, etc.) with **zero directory mismatches**.
* No team member's package will overwrite or conflict with another team member's package files because their domain routes, controllers, views, and services are strictly segregated.
