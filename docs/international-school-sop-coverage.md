# International-School SOP Coverage Checklist

**Authority:** [`sms-49day-laravel-sop.html`](../sms-49day-laravel-sop.html), especially §§4, 7, Appendix A and Appendix C.  
**Dataset context:** private/international school. The demo seeder is repeatable in local/testing and must not run in production.

## Roles → entities → operations → data

CRUD notation follows SOP §7.2: C=create, R=read/list/detail, U=update, D=soft delete. `own` or `assigned` scopes apply where noted. Export, approve and publish are called out separately. A dash means no access.

| Role | SOP entities and allowed operations | Scope / seed data |
|---|---|---|
| Super Admin | Users CRUD+export; roles/permissions CRUD; settings R/U; academic years/terms, departments/programs/subjects/curriculum, classes/sections, staff, students, guardians, student documents, class-subjects, enrollments, timetable, attendance, exams/exam-subjects, fee structures: CRUD (exports where the SOP grants E); marks R/U/export; results R/approve/publish/export; report cards R/export; invoices C/R/U/D/export; payments/receipts C/R/export; announcements CRUD/publish; reports R/export all; audit logs R/export; admin dashboard | System-wide demo account; all 240 students and all academic/finance workflows |
| Institution Admin | Same as Super Admin except roles/permissions R only; no audit-log delete; invoices C/R/U/export (no delete); payments R/export (no create); all remaining permissions follow the §7.2 matrix | Institution-wide records; role account and institution settings |
| Registrar | Users R; academic years/terms R; departments/programs/subjects/curriculum R/export; classes/sections C/R/U; staff R; students CRUD/export; guardians and student documents CRUD; class-subjects C/R/U; enrollments CRUD/export; timetable C/R/U; attendance R/U/export; exams and exam-subjects CRUD; marks R/U/export; results R/approve/publish/export; report cards R/export; fee structures/invoices/payments R; announcements C/R/U/publish within permitted scope; academic reports R/export | Admissions, enrollment, student records, attendance review and published results |
| Teacher | Academic catalogue, classes, own staff profile, own-class students/guardians, assigned class-subjects/enrollments/timetable: R; attendance C/R/U within the edit window; assigned exams R; assigned marks C/R/U until publication lock; assigned results/report cards R; announcements R; own-class academic/attendance reports R/export | 12 teaching staff, assignments, subject offerings, timetable, attendance and marks |
| Accountant | Settings, academic catalogue, classes, staff, student identity, guardians and enrollments R; fee structures CRUD; invoices C/R/U/export; payments/receipts C/R/export; announcements R; financial reports R/export | Fee structures, invoices in varied payment states, payments, receipts and currency-separated balances |
| Student | Own student record, guardian links, assigned academic catalogue, class/subjects, enrollment, timetable, attendance, published exams/results/report card, own invoices/payments, targeted announcements: R only | 240 student accounts and their linked admissions, academic and billing records |
| Guardian (SHOULD-HAVE S1) | Linked students, guardian links, student documents, attendance, published results/report card, invoices/payments and targeted announcements: R only; no write operations | Read-only linked-child portal demo; access is limited to linked students |

The executable permission set is seeded by `RolePermissionSeeder`. It uses permission synchronization so repeated seeding removes stale grants. Record-level policies remain authoritative for teacher assignment, student ownership and guardian-child linkage.

## Education levels and curriculum data

| International structure | Seeded levels / program tracks | Seeded assessment scales |
|---|---|---|
| Early Years | Nursery, Pre-K, Reception | IB PYP / early-years achievement |
| Primary | Grade 1–5 and British Year 1–6 | IB PYP, Cambridge Primary, American Elementary |
| Lower Secondary / Middle School | Grade 6–8 and British Year 7–9 | IB MYP, Cambridge Lower Secondary, American Middle School |
| Upper Secondary / High School | Grade 9–10; Grade 11–12 | Cambridge IGCSE A*–G; Edexcel International GCSE 9–1; American GPA; IB Diploma; Cambridge/Edexcel AS & A Level |
| Subjects and calendar | Language, mathematics, sciences, humanities, arts, computing, AP and IB-specific subjects; Sinhala, Hindi, Tamil, English, French, Spanish, Arabic, Mandarin, Japanese, Kiswahili, Malay, isiZulu and Afrikaans offerings; academic year, terms, offerings, weekly timetables | IB 1–7, Cambridge achievement bands, IGCSE A*–G, Edexcel 9–1, A-Level A*–E and American GPA |

Students are assigned to a year group, section and program that share the same curriculum track. Demo families and student records use international names and nationalities; fee records use LKR, USD, GBP, EUR or AED and are reported by currency rather than added together.

## SOP module and workflow → seed data checklist

| SOP scope | Seeded demo coverage |
|---|---|
| M1–M3: authentication, RBAC, users | Local role accounts for the SOP roles; password and role workflows |
| M4–M6: settings, academic years/terms, departments/programs/subjects/curriculum, classes/sections | International tracks and the full age/grade structure above |
| M7–M10: staff, admissions/students, guardians/documents, search/filter/export | 12 staff; 240 multinational students; linked guardians; admissions file records |
| M11–M14: offerings, enrollment, timetable, student attendance | Assigned offerings and enrollments, weekly timetable and six weeks of attendance |
| M15–M18: exams, marks, grading, result approval/publication/report cards | Two exams with marks and published term results using program-specific scales |
| M19–M21: fee structures, invoice generation, payments/receipts | Multi-currency fee schedules, invoices with mixed payment states and payment history |
| M22–M23: announcements and role dashboards | Targeted announcements and role accounts for dashboards |
| M24–M25: 11 reports and audit logs | Operational records feed reports; sensitive workflows are auditable |
| M26: UI foundation | Existing application layout, navigation and shared UI components |
| S1–S8 SHOULD-HAVEs | All eight are in the requested scope. Their individual implementation/data status is listed below; missing items remain gaps rather than being treated as optional. |
| §4.3 POST-MVP items | Included in the user's expanded scope even though the authoritative SOP classifies them as post-MVP or excluded. Implemented modules are seeded; outstanding items are explicit gaps below. |

## Expanded scope: SHOULD-HAVE and POST-MVP status

| SOP item | Current implementation and sample-data status |
|---|---|
| S1 Guardian read-only portal | Implemented and seeded with linked-child records, attendance, published results and invoices; authorization is child-scoped. |
| S2 CSV import | Import UI/service and field-level validation exist. Import runs are user-driven; no persistent fake import run is seeded. |
| S3 Staff attendance | Implemented and seeded with staff attendance records. |
| S4 Dashboard charts | **Gap:** dashboards provide role-based counts, but chart visualizations are not implemented. |
| S5 PDF versions of all 11 reports | PDF report routes/controller exist for the report catalogue. Export output and print-color behavior still require authenticated UAT for every role. |
| S6 Scheduled absentee digest | Command, mail class and template exist; attendance samples are seeded. Scheduler delivery and recipient acceptance are not verified here. |
| S7 Saved filters | **Gap:** no saved-filter entity or user workflow was found. |
| S8 Two-factor authentication | Setup/challenge flows exist. Secret material is intentionally not seeded; security enrollment must be completed by each user. |
| Library | Implemented; branch-visible catalogue, copies, loans and fines are seeded. |
| Multi-campus | Implemented; three private-school campuses and branch-scoped records are seeded. |
| Transcripts and CGPA across years | Transcript/CGPA service, student/guardian views and PDFs exist. The demo currently supplies the 2026–2027 academic year, so multi-year transcript data is a **gap**. |
| Online payments | PayHere sandbox workflow exists. A pending LKR transaction is seeded without contacting the payment provider; live credentials or successful payments are not fabricated. |
| Messaging | Private messaging is implemented and seeded with role-appropriate conversations. |
| HR, payroll and leave | Implemented and seeded with staff, payroll and leave workflows. |
| Hostel, transport, inventory and cafeteria | Implemented and seeded with international-school student assignments, routes, stock and orders. |
| Public REST API and tokens | Versioned student API and token management exist. One expired student token is seeded for safe UI demonstration; no usable bearer secret is stored. |
| Elective self-registration | Student register/drop workflow and elective curriculum offerings exist. Course/enrollment data are seeded; add/drop interaction remains to be verified in authenticated UAT. |
| Biometric/RFID attendance | Signed event ingestion exists. A local-only device and matched/unmatched events are seeded; no physical device is connected. |
| SMS notifications | **Gap:** an outbound gateway service exists, but no persisted SMS workflow/history or safe delivery sample is implemented. Demo seeding never sends external SMS. |
| Multilingual UI and RTL | **Gap:** application UI remains English and left-to-right. |
| Legacy migration | **Gap:** validated CSV import is not a legacy migration plan, mapping, reconciliation or rehearsed import. |
| Mobile app | **Gap:** no native mobile application is present. Responsive web layout is not a mobile app. |

## Loading and acceptance

- Automatic: `php artisan migrate --seed` or `php artisan db:seed` in local/testing; `DatabaseSeeder` routes production to `ProductionSeeder`. The 5,000-student dataset is `php artisan db:seed --class=PerfSeeder`; it is local/testing-only and can be followed by the module-complete `FullSampleDataSeeder`.
- Manual: use the existing role-authorized UI forms/API endpoints. Student CSV imports require `document_type`, `issuing_country` and `document_number`; CSV bulk import is dry-run validated and is not a production legacy migration.
- Re-run safety: seeders use stable keys, `firstOrCreate`/`updateOrCreate`, and `syncPermissions`; run the functional seed test to verify repeatability and record counts.
- The 5,000-student performance dataset is seeded by `PerfSeeder` with international names, passport records, country-coded contacts and Grade 6–12 curriculum tracks. Its exact record count and repeatability are tested. The combined sequence (`PerfSeeder` followed by `FullSampleDataSeeder`) now passes under the test process's normal 128 MiB limit; SOP response-time/concurrency NFR-1 measurements remain unverified.

## International form and import validation

- Student, guardian and teacher create/edit forms, student profile, and institution-user forms validate personal names as Unicode letters and spaces. Name inputs also discard digits and symbols as entered.
- Student, guardian, teacher, student-profile, branch, supplier, transport-driver and online-payment phone forms use country calling-code selectors, metadata-based national-number lengths, digits-only input, and E.164 normalization. Student document type, issuing country and document number are mandatory; passports use ICAO alphanumeric format and national IDs use country-specific patterns for the countries with a national identifier. Australia has no universal national ID, so Australian records must use a passport. Other issuing countries use the specified general alphanumeric fallback. Authentication and user forms show email/password errors against their respective fields.
- Student, staff and guardian CSV imports validate names and country-specific phone numbers using optional `phone_region` values, normalize valid phones and identify the failing CSV field in each row report.
- The UI language remains English in accordance with SOP Appendix A; country-specific UI translation and RTL are post-MVP.

## Verification boundaries

The listed permissions and workflows are the SOP cross-check, not a claim that every CRUD screen has passed manual end-to-end acceptance. Automated checks cover the seven SOP roles, representative permission boundaries, international demo data, extended-scope demo records, the repeatable 5,000-student dataset, the combined full-module seed sequence, international input rules and CSV validation. Missing items in the expanded-scope table, full signed workflow UAT, every screen/operation and SOP performance thresholds remain open as noted in `known-issues.md`.
