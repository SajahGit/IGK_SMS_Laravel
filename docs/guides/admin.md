# Administrator Guide (admin.md)

**Project:** IGK School ERP — Laravel 12.70.0  
**SOP Reference:** §29 Handover Artefact #6  
**Owner:** D2+D3  

---

## Overview

Setting up an academic year, catalogue, classes, users/roles, fee structures, exams, publishing results, reports, audit log.

---

## 1. Academic Year & Term

The `school:sync-lifecycle` command seeds and activates the date-matching year and term. It runs daily at 00:05; local web requests also trigger it once per day under a cache lock.

- The 2026–2027 terms run 2026-09-01–2026-12-18, 2027-01-18–2027-04-09, and 2027-04-26–2027-07-16.
- The 2027–2028 terms run 2027-09-01–2027-12-17, 2028-01-17–2028-04-07, and 2028-04-24–2028-07-14.
- Later academic years reuse the 2027–2028 month/day pattern.
- Future terms remain upcoming until their date ranges begin. In calendar gaps, no term is current.
- At term changes, active subject offerings, eligible student subject enrollments, and clash-free timetable slots are carried forward. Unpublished scheduled assessments and calendar announcements are created, and transitions are audited.
- At each September year change, active students are promoted only when a next class with the same program, campus, and section exists. Year 13, DP Year 2, and Grade 12 students graduate; unmatched promotions are logged and skipped.

To safely exercise the transitions in local/testing environments:

```sh
php artisan school:sync-lifecycle --simulate-date=2027-01-18
```

`--simulate-date` is rejected outside local/testing. Only one academic year and one term are current at a time.

Admin-created year or term records remain supported; the lifecycle command reconciles their current state on its next run.

---

## 2. Catalogue (Departments, Programs, Subjects, Curriculum)

1. Go to **Admin > Departments** and create departments (e.g. "Science", "Arts").
2. Go to **Admin > Programs** and create programs under departments (e.g. "Grade 10").
3. Go to **Admin > Subjects** and create subjects under programs (e.g. "Mathematics", code "MATH", 4 credit hours).
4. Go to **Admin > Curriculum** to map subjects to programs per term. Mark subjects as elective if applicable.

---

## 3. Classes and Sections

1. Go to **Admin > Classes**.
2. Create classes with name, section, and capacity (e.g. "Grade 10", section "A", capacity 40).
3. Enrollment is blocked when a class reaches capacity.

---

## 4. Teachers and Class-Subject Assignments

1. Go to **Admin > Teachers** to create teacher profiles (linked to user accounts with the Teacher role).
2. Go to **Admin > Class Subjects** to assign a teacher to a class-subject combination.
3. Teachers can only enter marks for their assigned class-subjects.

---

## 5. Students and Guardians

1. Go to **Admin > Students** to admit students. Each student gets a unique admission number.
2. Go to **Admin > Guardians** to link guardians to students.
3. Guardians can view their linked children's records only.

---

## 6. Users and Roles (RBAC)

1. Go to **Admin > Roles** to manage the SOP roles (Super Admin, Institution Admin, Registrar, Teacher, Accountant, Student, Guardian, Librarian).
2. Go to **Admin > Users** to create and manage system user accounts.
3. **Search & Filter Directory:**
   - **Full-Text Search:** Instantly filters by user name, email address, or phone number.
   - **Role Filter:** Filter user list by specific system role (e.g., Accountant, Teacher, Student).
   - **Campus / Branch Filter:** Filter by assigned campus or view cross-campus staff.
   - **Status Filter:** Toggle between Active and Inactive users.
   - **Count & Pagination:** Total matching users are summarized in the table footer with query-string preservation across pagination pages.
4. Users can be deactivated; deactivated users cannot log in.
5. Permissions are managed via Spatie Laravel Permission.

The shared dropdown component covers regular role, campus, and status selects and keeps field-bound menus below their inputs. Its cross-viewport component results and the remaining authenticated-page coverage are tracked in the [global dropdown UI guide](./global-dropdown-ui.md).

---

## 7. Fee Structures

1. Go to **Admin > Fee Structures**.
2. Create fee structures for a class and term (e.g. "Term 1 Tuition Fee", amount 15000.00).
3. Decimal amounts are supported (BCMath precision).
4. Invoices are generated from fee structures.

---

## 8. Invoices and Payments

1. Go to **Admin > Invoices** to generate invoices for students/terms.
2. Invoice numbers are sequential and unique (idempotent generation).
3. Go to **Admin > Payments** to record payments against invoices.
4. Payments over the balance are rejected (422).
5. Partial payments update balance; status becomes `partially_paid` then `paid`.
6. Receipts are generated for each payment (`/admin/receipts/{payment}`); use **Download PDF** for the official receipt PDF.
7. Download invoice PDFs from the invoice details page. Students can download only their own invoice.
8. Super Admin can reverse a payment with a mandatory reason; balance is restored; cannot reverse twice.

---

## 9. Exams and Marks

1. Go to **Admin > Exams** to create exams (class-subject-term, max marks, weight).
2. Teachers enter marks via **Teacher > Marks** for their assigned class-subjects only.
3. Marks are validated against max marks; negative or out-of-range marks are rejected.
4. Admin publishes results via **Admin > Exams > Publish**.
5. Once published, marks are locked; teachers cannot edit. Only unpublish (authorized) allows changes and is audited.
6. Students and guardians can view only published results; unpublished results are hidden.

---

## 10. Grading and GPA

1. Grade scale is seeded by `GradeScaleSeeder` (A+ = 4.00 down to F = 0.00, pass mark 40%).
2. Grades are calculated per paper from the grade scale.
3. Term GPA is calculated from grade points across subjects; absent results score zero; null marks are excluded.
4. Results with no data return null GPA.

---

## 11. Report Cards

1. Go to **Students > {student} > Report Card** to view/print the report card or download its PDF.
2. The PDF renders the configured institution header, student details, published term results, grades, GPA, attendance, and signature lines.

---

## 12. Reports (11 Core Reports)

1. Go to **Reports** to open each report:
   - Enrollment Register (CSV export)
   - Attendance Summary Per Student (CSV export)
   - Class Result Summary (CSV export)
   - Student Financial Ledger (CSV export)
   - Plus 7 additional report views
2. Reports require academic or financial permissions based on type.
3. CSV endpoints stream data for large datasets.
4. Reports handle empty data without error.

---

## 13. Announcements

1. Go to **Admin > Announcements**.
2. Create announcements with target role (all, teacher, student, parent).
3. Set published and expiry dates.

---

## 14. Audit Log

1. Go to **Admin > Audit Log**.
2. Authenticated create/update/delete changes to core academic, user, role, attendance, and setup records are recorded with actor, model, before/after values, IP address, and timestamp.
3. Payments, marks, enrollment, student documents, fee structures, announcements, timetable changes, role/permission assignments, authentication events, and password changes are also recorded.
4. Passwords, reset tokens, and other secret values are excluded from audit snapshots.

---

## 15. Student Documents

1. Go to **Admin > Students > {student} > Documents**.
2. Upload files (jpg/png/pdf < 5MB) to private storage.
3. Download via authorized route only (`/documents/{document}/download`).
4. Files are not directly accessible by URL; served only via policy-checked route.

---

---

## 16. Multi-Branch Campus Operations & Transfers

1. **Campus Isolation**:
   - Each branch operates with isolated classes, sections, and admissions enforced by the `BranchOwned` global scope.
2. **Student & Teacher Branch Assignment**:
   - Assign home campuses on student admission and teacher registration forms.
   - When transferring campuses, go to **Admin > Transfers** or the student/teacher edit page.
   - The system reassigns the branch and logs an immutable transfer history entry in `branch_transfers` with timestamp, originating campus, destination campus, and authorizer ID.

---

## 17. Dedicated Super Admin Salary Deductions Console

1. **Strict Security Isolation**:
   - Access is restricted exclusively to **Super Admin** via `/admin/salary-deductions`.
   - Branch-level HR and accountants cannot manipulate base salary deductions.
2. **Automatic Attendance Deductions Policy**:
   - Super Admin can configure how absent days and unpaid leaves are penalized:
     - **Dynamic Pro-Rata**: Deducts `(Base Salary / 30) * Unpaid Days`.
     - **Fixed Daily Penalty**: Deducts a flat amount (e.g. Rs. 2,000 / day) regardless of base salary.
   - Policy settings are updated dynamically and take effect immediately in the next payroll run.
3. **Manual Custom / Wished Amount Deductions**:
   - Super Admin can click **New Deduction** to record any custom amount for any staff member.
   - Select reason/category: Salary Advance, Loan Repayment, Property Damage, Disciplinary Fine, or Other.
   - Pending deductions are automatically aggregated and marked `applied` when monthly payroll is generated.
4. **Transparency**:
   - Every deduction created appears itemized with its calculation basis in the affected staff member's personal portal (`/teacher/profile`).

---

## 18. HR Payroll Generation & Official Payslips

1. Go to **Institution Admin > HR Payroll**.
2. Create or generate a monthly payroll run for the selected branch.
3. The payroll engine evaluates employee earnings, applies Sri Lankan statutory formulas (EPF 8%/12%, ETF 3%, progressive APIT), and calculates dual-mode deductions.
4. View payslips (`/admin/hr/payroll/payslip/{entry}`):
   - Fully immune to night/dark mode: uses scoped `.payslip-sheet` with high-contrast text and clean borders.
   - Instant A4 portrait physical printing via **Print Payslip** button.

---

## 19. Universal Reverse-Chronological Listing Standard

1. **Top-Level Visibility for New Entries**:
   - Whenever an administrator or staff member adds a new record in any module (Cafeteria items, Hostel halls/rooms, Inventory stock items, Transport routes/vehicles, Classes, Subjects, Departments, Academic Programs, Curricula, Fee Structures, Invoices, Library books/copies, Suppliers, Purchase Orders, Accounts, Announcements, or System Users), the newly created item **automatically appears at the very top of the table** (`latest('id')` / `orderByDesc('id')`).
   - Staff no longer need to navigate to the last pagination page or hunt alphabetically for newly added items.

---

## 20. Centralized Single-Alert Notifications & Cafeteria Multi-Branch Stock

1. **Single Notification Alert**:
   - Flash feedback (`success`, `status`, `warning`, `error`) is centralized in `LayoutApp.blade.php`.
   - Redundant in-page alert components were purged across 24 child views, guaranteeing every action displays exactly one animated, auto-dismissing banner.
2. **Multi-Branch Cafeteria & Stock Availability**:
   - Cafeteria items are linked with `inventory_items` and multi-campus branches via `BranchIsolationServiceProvider`.
   - When an administrator creates a menu item, active stock from the home campus or global stock is immediately visible in student ordering portals across that branch.

---

## Role Summary

| Role | Can Do |
|---|---|
| Super Admin | Full system governance, dedicated Salary Deductions console, policy configuration, payment reversals |
| Institution Admin | Campus operations, classes, teachers, student admissions, HR payroll generation |
| Registrar | Academic admissions, enrollment registers, dynamic class/section allocations |
| Teacher | Assigned classes, student attendance, marks entry, self-service profile & payslip portal |
| Accountant | Finance only (fee structures, invoices, payments, receipts) |
| Student | Own data only (results, invoices, report card) |
| Parent | Linked children only |
