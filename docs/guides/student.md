# Student & Guardian Portal Guide (student.md)

**Project:** IGK International School ERP — Laravel 12.71.0  
**SOP Reference:** §29 Handover Artefact #8  
**Target Roles:** Student, Guardian / Parent  

---

## 1. Overview & Authentication

Students and guardians access their dedicated, secure portals with Role-Based Access Control (RBAC). Multi-tenant data scoping ensures students and parents only access records belonging to their profile or linked children.

- **Student Portal**: `/student/dashboard`
- **Guardian Portal**: `/guardian/dashboard`
- **Credentials**: School-issued email (e.g. `student@school.com` / `parent@school.com`) and password.
- **Password Administration**: Under institutional policy, student passwords are managed exclusively by the School Administrator to ensure data integrity and prevent lockout. Students cannot change passwords self-service.
- **Two-Factor Authentication (2FA)**: Optional/enforced TOTP authenticator setup for enhanced account protection.

---

## 2. Student Self-Service Capabilities

### A. Academic Results & Progress
- **Published Results**: Students can only view marks and grades for exams that have been officially published by the administration.
- **Term Result Cards**: View detailed breakdowns of subjects, weighted CA/Exam marks, grade scale descriptors (A*-U, IB 7-point, GCSE 9-1), and GPA.
- **PDF Report Card Download**: Instant 1-click download of official, high-resolution signed term report cards.
- **Multi-Year Transcripts**: View academic transcript records and cumulative GPA progression across academic years.

### B. Daily Attendance Tracking
- **Real-Time Attendance Roll**: View session-by-session attendance records (Present, Absent, Late, Excused) by subject, class, and date.
- **Attendance Percentage**: Live calculation of overall term attendance percentage with alerts for low attendance.

### C. Class Timetable & Schedule
- **Weekly Schedule**: Real-time display of active weekly class slots, period timings, classroom/room allocations, and assigned subject teachers.

### D. Elective Subject Registration
- **Elective Selection**: Browse available elective streams for the term and register subject choices within class capacity limits.

---

## 3. Campus Life & Ancillary Services

### A. Cafeteria Ordering & Account
- **Menu Catalog**: Browse available meal sets, snacks, and beverages categorized by day and meal slot. Newly added menu items always appear at the top of the menu list.
- **Multi-Branch Stock Synchronization**: Students automatically view menu items available at their enrolled campus branch (or campus-wide stock).
- **Cafeteria Orders**: Place meal orders directly through the portal and view order status (Pending, Confirmed, Delivered). Ordering reserves real inventory stock in real-time.

### B. Hostel & Boarding Services
- **Hostel Allocation**: View assigned hostel building, room number, floor, room type, and room warden contact information.
- **Hostel Guidelines & Leave**: Access hostel rules and submit boarding leave/outing requests.

### C. School Transport & Bus Routes
- **Assigned Route**: View designated bus route, vehicle number, driver name, and driver contact number.
- **Pick-up / Drop-off Stops**: View assigned bus stop location and scheduled morning pickup and afternoon drop-off timings.

### D. Library & Learning Management System (LMS)
- **Library Catalog**: Search available physical books, check borrowing eligibility, and monitor due dates to avoid library fines.
- **LMS Courses & Lessons**: Access digital learning courses, view uploaded lesson materials, download study notes, and submit homework/assignments online.

---

## 4. Invoices & Fee Payments

- **Invoice Ledger**: View all issued term fee invoices, invoice breakdown (tuition, facility, lab, exam fees), and payment status (Paid, Partial, Unpaid).
- **Online Payment**: Pay outstanding fees online securely via integrated PayHere gateway.
- **Receipts**: Download official PDF fee receipts for verified payments with receipt numbers and remaining balance.

---

## 5. Confidential Private Messaging (`/messages`)

- **End-to-End Private Messaging**: Communicate directly and confidentially with subject teachers, class teachers, and administrative personnel.
- **Categorized Recipient Picker**: Easily find teachers and campus coordinators.
- **Notifications**: Receive instant inbox alerts when a teacher sends academic feedback or term announcements.

---

## 6. Guardian / Parent Portal Features (`/guardian/dashboard`)

Guardians have centralized visibility over all their registered children across branches:
1. **Multi-Child Switcher**: Effortlessly switch between linked sons/daughters in the top navigation bar.
2. **Attendance Monitoring**: Monitor child roll-call attendance in real time.
3. **Academic Monitoring**: Review report cards, exam marks, and teacher remarks.
4. **Fee Management**: Review invoices for all children, view consolidated family fee balances, and download official receipts.
5. **Direct Teacher Contact**: Message class teachers directly regarding student progress or attendance justifications.
