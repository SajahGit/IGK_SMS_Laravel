# Administrator Guide (admin.md)

**Project:** IGK School ERP — Laravel 12.70.0  
**SOP Reference:** §29 Handover Artefact #6  
**Owner:** D2+D3  

---

## Overview

Setting up an academic year, catalogue, classes, users/roles, fee structures, exams, publishing results, reports, audit log.

---

## 1. Academic Year & Term

1. Go to **Admin > Academic Years**.
2. Create an academic year (e.g. "2026") and mark it as current.
3. Go to **Admin > Terms**.
4. Create terms (e.g. "Term 1") under the academic year.
5. Only one academic year and one term can be current at a time (is_current invariant enforced).

> Changing the current year/term automatically unsets the previous current record.

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

1. Go to **Admin > Roles** to manage the six SOP roles (Super Admin, Institution Admin, Registrar, Teacher, Accountant, Student).
2. Go to **Admin > Users** to create users and assign roles.
3. Users can be deactivated; deactivated users cannot log in.
4. Permissions are managed via Spatie Laravel Permission.

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

## Role Summary

| Role | Can Do |
|---|---|
| Super Admin | Full access including roles/permissions, user management |
| Institution Admin | Broad admin access, no role management |
| Registrar | Academic focus, limited finance write (read-only invoices/payments) |
| Teacher | Assigned classes only, attendance, marks entry (until locked) |
| Accountant | Finance only (fee structures, invoices, payments) |
| Student | Own data only (results, invoices, report card) |
| Parent | Linked children only |
