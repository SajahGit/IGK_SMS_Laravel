# Teacher Guide (teacher.md)

**Project:** IGK International School ERP — Laravel 12.71.0  
**SOP Reference:** §29 Handover Artefact #7  
**Target Role:** Teacher / Faculty Member  

Sign in with a **Teacher** account; you land on `/teacher/dashboard`. You only see classes, subjects, and records assigned to you and your home campus.

---

## 1. My Profile & Self-Service Portal (`/teacher/profile`)
1. Click your name or **Profile** in the navigation bar to access your personal dashboard.
2. **Personal & Campus Details**: View your employee ID, official email, phone, designation, department, and assigned campus/branch.
3. **Academic Timetable**: View your assigned weekly class schedule and subjects.
4. **Leave & Personal Attendance History**:
   - View available leave balance, approved leaves, and pending leave applications.
   - Under **Leaves & Attendance History** (also accessible via `/my-attendance` and the topbar clock icon), inspect your personal daily check-in/check-out times, total working hours, punctuality status (`Present`, `Late`, `Half Day`), notes, and monthly KPI cards (**Days Present**, **Total Hours**, **Avg Daily**, **On-Time Rate**).
5. **Salary & Deductions Transparency**:
   - Access all past and current monthly payslips.
   - Inspect itemized earnings (Basic salary, allowances).
   - Inspect itemized **Automatic Attendance Deductions** (unpaid leaves and absent days with rate basis).
   - Inspect itemized **Manual Deductions** applied by Super Admin (salary advances, loans, property damage, disciplinary fines).
   - View statutory contributions (EPF 8% employee / 12% employer, ETF 3% employer, APIT income tax).
   - Download or print official A4 portrait payslips with zero night-mode styling conflicts.

---

## 2. Daily Attendance Roll-Call (`/teacher/attendance`)
1. Open **Attendance** and pick your assigned class, subject, and date.
2. Mark each student **Present**, **Absent**, **Late**, or **Excused**; add remarks where needed.
3. Save roll call. Records are saved atomically inside database transactions.
4. Sessions remain editable within the configured 48-hour edit window; after that, contact your Campus Administrator.

---

## 3. Marks & Examinations (`/teacher/exams`)
1. Open **Exams**, choose the active exam session, and pick your assigned class/subject.
2. Enter marks for the entire class in one unified grading sheet.
3. Tick **Absent** for students who missed the exam rather than entering 0, ensuring GPA integrity.
4. Save marks. Marks lock permanently once official term results are published by the administration.

---

## 4. Class Timetable (`/teacher/timetable`)
Open **Timetable** to inspect your active weekly class slots, period timings, and assigned room numbers.

---

## 5. Learning Management System (LMS) (`/teacher/lms`)
1. Create and manage digital course modules for your assigned subjects.
2. Upload lesson plans, syllabus outlines, and downloadable learning resources (PDFs, presentations).
3. Post student assignments with due dates and grading criteria.
4. Review submitted homework and assign scores with personalized feedback.

---

## 6. End-to-End Private Messaging (`/messages`)
1. Send confidential 1-on-1 private messages to students, guardians, and administrative staff.
2. Instant message filtering: Search recipients by name, role, or campus branch.
3. Real-time notifications: Unread indicators alert you to new communications immediately.


---

## 7. My Learning Materials (`/teacher/learning-materials`)
1. When enabled by Admin (`teacher_uploads`, `teacher_video_uploads`, `teacher_pdf_uploads`, and per-user `lms_can_upload_materials`), upload videos and PDFs directly to the Common Learning Materials Library.
2. Edit or delete your own uploaded materials and generate/revoke secure `/secure-material/{token}` share links.
3. Admin-controlled download restrictions (`allow_download`) and expiry policies cannot be overridden by teachers.
