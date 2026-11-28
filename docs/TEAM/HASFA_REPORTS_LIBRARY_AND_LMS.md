# Team Member: HASFA
## Domain: The 11 Master Reports Suite, Library Information System & LMS

**Assigned Engineer:** Hasfa  
**System Role:** Institutional Analytics / Reporting Lead / Librarian  
**Deliverable Zip File:** `HASFA_REPORTS_PACKAGE.zip` (located in `G:\ALL DOCS\packages\` and `G:\ALL DOCS\`)  

---

### 1. Functional Scope & Key Responsibilities

Hasfa owns data analytics, statutory compliance reporting, document exports, the library circulation desk, and online learning modules:
1. **The 11 Master Operational & Statutory Reports:**
   - **Enrollment Register Report:** Complete census of admitted students per campus, program, and class with CSV streaming export.
   - **Attendance Summary Per Student Report:** Daily present/absent counts, excused absences, and attendance percentage.
   - **Class Attendance Summary Report:** Cohort-level daily roll call analytics.
   - **Class Result Summary Report:** Grade distributions and pass rates per subject and term.
   - **Subject Performance Report:** Comparative subject analytics, high/low/average score benchmarks.
   - **Term Results Report:** Published cohort grades and GPA rankings.
   - **Student Financial Ledger:** Comprehensive billing and transaction statement per student.
   - **Fee Collection Report:** Multi-currency payment receipts, cashier breakdowns, and collection methods.
   - **Defaulters Action List:** Aging overdue invoice report with balance due and guardian contact details.
   - **Official Student Report Card (`/pdf/report-card`):** Pixel-perfect printable A4 PDF featuring institution crest, grades, GPA, attendance, and principal signature lines. Standardized with `@page { size: A4 portrait; margin: 10mm 12mm; }` eliminating clipping and horizontal stretching.
2. **High-Performance Memory-Safe CSV Streaming & PDF Generation:**
   - Powered by `CsvStreamer` support service; streams multi-thousand row datasets without memory spikes.
   - Pixel-perfect DomPDF generation with fixed table layouts, box-sizing containment, and page-break rules.
3. **Library Information System (LIS) & Circulation Desk:**
   - Book cataloguing, author/publisher directories, inventory barcode tracking, and loan issuance/return workflows.
   - Global Auto-Sort by Latest Activity (`updated_at DESC`, `id DESC`) across library catalogues, authors, and active loans.
3. **Library Information & Circulation System (`/library`):**
   - Book cataloging (Title, ISBN, Category, Authors).
   - Physical copy inventory and barcode tracking.
   - Circulation desk (Check-out loans, Return check-in, Overdue fine calculation).
4. **Learning Management System (LMS):**
   - Course creation, syllabus modules, and lesson content.
   - Student homework assignments and submission uploads with teacher grading.

---

### 2. Complete File Inventory (37 Files)

#### Routes:
* `routes/reports.php`
* `routes/library.php`

#### Controllers:
* `app/Http/Controllers/Report/AttendanceReportController.php`
* `app/Http/Controllers/Report/AttendanceSummaryPerStudentReportController.php`
* `app/Http/Controllers/Report/ClassAttendanceSummaryReportController.php`
* `app/Http/Controllers/Report/ClassResultSummaryReportController.php`
* `app/Http/Controllers/Report/DefaultersReportController.php`
* `app/Http/Controllers/Report/EnrollmentRegisterReportController.php`
* `app/Http/Controllers/Report/FeeCollectionReportController.php`
* `app/Http/Controllers/Report/ReportPdfController.php`
* `app/Http/Controllers/Report/ResultReportController.php`
* `app/Http/Controllers/Report/StudentFinancialLedgerReportController.php`
* `app/Http/Controllers/Report/StudentReportController.php`
* `app/Http/Controllers/Report/SubjectPerformanceReportController.php`
* `app/Http/Controllers/Library/LibraryController.php`
* `app/Http/Controllers/Library/LibraryMasterController.php`
* `app/Http/Controllers/Teacher/LmsController.php`
* `app/Http/Controllers/Student/LmsController.php`

#### Support Services:
* `app/Support/CsvStreamer.php`

#### Models:
* `app/Models/LibraryBook.php`
* `app/Models/LibraryAuthor.php`
* `app/Models/LibraryCategory.php`
* `app/Models/LibraryCopy.php`
* `app/Models/LibraryFine.php`
* `app/Models/LibraryLoan.php`
* `app/Models/LmsCourse.php`
* `app/Models/LmsLesson.php`
* `app/Models/LmsAssignment.php`
* `app/Models/LmsSubmission.php`
* `app/Models/LmsCourseMaterial.php`

#### Blade Views:
* `resources/views/Reports/` (ReportEnrollmentRegister, ReportAttendance, ReportClassAttendanceSummary, ReportAttendanceSummaryPerStudent, ReportResults, ReportClassResultSummary, ReportSubjectPerformance, ReportFeeCollection, ReportDefaulters, ReportStudentFinancialLedger, ReportStudent)
* `resources/views/Library/` (LibraryDashboard, LibraryMasterIndex, LibraryCopiesIndex, LibraryLoansIndex, LibraryFinesIndex, LibraryBookEdit)
* `resources/views/Pdf/PdfReportCard.blade.php`

#### Feature Test Suites:
* `tests/Feature/ReportsParityTest.php`
* `tests/Feature/StudentRegisterExportTest.php`
* `tests/Feature/PdfDocumentsTest.php`
* `tests/Feature/Phase5AnnouncementsAuditReportsTest.php`

---

### 3. Step-by-Step Verification Commands for Hasfa

```powershell
# Run all reports parity, CSV streaming, and PDF generation tests
php vendor/bin/phpunit tests/Feature/ReportsParityTest.php tests/Feature/StudentRegisterExportTest.php tests/Feature/PdfDocumentsTest.php tests/Feature/Phase5AnnouncementsAuditReportsTest.php
```


---

## Secure Video & PDF Learning Materials Update (2026-11-28)
- Added `LearningMaterialService`, `InstitutionAdmin\LearningMaterialController`, `Teacher\LearningMaterialController`, `Student\LearningMaterialController`, and `LearningMaterialDeliveryController`.
- Implemented private `local` disk storage (`storage/app/private`), 64 KB HTTP `206` Range streaming, PDF.js viewer, `allow_download` enforcement, non-destructive expiry (files kept on disk; access/share links blocked), SHA-256 `/secure-material/{token}` links, live search-as-you-type, and per-user/bulk Video & PDF access permissions.
