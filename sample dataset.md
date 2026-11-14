# Manual QA Sample Dataset

இந்த document local/test environment-ல் role-wise manual QA செய்யும் dataset மற்றும் steps. இது [`sms-49day-laravel-sop.html`](./sms-49day-laravel-sop.html)-ன் permission matrix, validation மற்றும் acceptance criteria-ஐப் பின்பற்றுகிறது. SOP-ல் உள்ள MVP role count பழையது; இந்த project-இன் தற்போதைய 8 roles-ஐ இங்கே பயன்படுத்தவும். புதிய teammate-க்கு `.env`, Git setup மற்றும் ஒரே demo data உருவாக்கும் படிகள் [`README-SETUP.md`](./README-SETUP.md)-ல் உள்ளன.

## 1. பாதுகாப்பான test setup

1. ஒவ்வொரு developer-மும் தங்களது local MySQL-ல் `school_management_system_testing_db` பயன்படுத்த வேண்டும்; அதே DB பெயரை ஒரே shared MySQL server-ல் ஒரே நேரத்தில் பகிர வேண்டாம். முழு setup [README-SETUP.md](./README-SETUP.md)-ல் உள்ளது.
2. `.env` target-ஐ `php artisan config:show database.connections.mysql.database` மூலம் உறுதி செய்த பிறகு புதிய schema-வில் `php artisan migrate --seed` இயக்கவும். `DatabaseSeeder` local/testing சூழலில் `FullSampleDataSeeder`-ஐ அழைக்கிறது.
3. Clean demo dataset-க்கு மட்டும் `php artisan migrate:fresh --seed` இயக்கவும். இது `.env`-ல் தேர்ந்தெடுத்த DB-இன் எல்லா tables/data-வையும் அழிக்கும்; தனிப்பட்ட disposable local DB என்பதை உறுதி செய்யாமல் இயக்க வேண்டாம்.
4. Demo logins அனைத்துக்கும் password `password`:

| Role | Demo email |
|---|---|
| Super Admin | `superadmin@school.test` |
| Institution Admin | `admin@school.test` |
| Registrar | `registrar@school.test` |
| Teacher | `teacher@school.test` |
| Accountant | `accountant@school.test` |
| Librarian | `librarian@school.test` |
| Student | `student@school.test` |
| Guardian | `guardian@school.test` |

5. 2FA **Super Admin, Institution Admin-க்கு மட்டுமே** கட்டாயம். Local-ல் முதல் setup-க்கு login பின் காட்டப்படும் setup code-ஐ பயன்படுத்தவும்; அடுத்த login-களுக்கு authenticator app-இன் புதிய code தேவை. மற்ற 6 roles password login முடிந்ததும் நேரடியாக தங்கள் dashboard-க்கு செல்ல வேண்டும்.
6. ஒவ்வொரு role-லும் add/edit/delete செய்ய முடியாது. அந்த role-ன் permission matrix-க்கு ஏற்ப அனுமதிக்கப்பட்ட CRUD மட்டும் செய்யவும்; read-only/immutable செயல்களுக்கு கீழே உள்ள deny/guard checks-ஐ எதிர்பார்க்கவும்.
7. கீழுள்ள பெயர்கள், emails, identifiers அனைத்தும் synthetic QA values. Seeded dropdowns-ல் class, term, branch, guardian, invoice, subject, exam போன்ற உறவுகளைத் தேர்ந்தெடுக்கவும்; ஊகித்து ID type செய்ய வேண்டாம்.

## 2. பொதுவான CRUD சோதனை முறை

ஒவ்வொரு sample record-க்கும்:

1. **Create:** role-க்கு உரிய menu-இல் Add/Create திறந்து கீழுள்ள values மற்றும் seeded relational dropdown values-ஐ நிரப்பி save செய்யவும். புதிய row/list entry சரியாகத் தோன்ற வேண்டும்.
2. **Read:** search/filter மூலம் அதே row-ஐக் கண்டுபிடித்து detail page-ஐத் திறக்கவும்.
3. **Update/Edit:** `QA-EDIT` அல்லது `-EDIT` suffix/குறிப்பைச் சேர்த்து save செய்யவும்; list, detail, dashboard/report-ல் மாற்றம் வந்ததைச் சரிபார்க்கவும்.
4. **Delete:** record-க்கு downstream data இல்லாதபோது மட்டும் delete செய்யவும். Confirmation வந்தால் உறுதிப்படுத்தி list-ல் மறைந்ததைச் சரிபார்க்கவும். Linked/posted/published data-க்கு delete மறுக்கப்படுவது சரியான behavior; அந்த record-ஐ வைத்திருக்கவும்.
5. ஒவ்வொரு form-லும் required field காலியாக விடுதல், invalid email/date/amount, duplicate unique value ஆகியவற்றைச் சோதிக்கவும். Inline validation error வர வேண்டும்; invalid record சேமிக்கப்படக் கூடாது.
6. Save/update/delete பிறகு refresh செய்து persistence, success/error message, audit history (அந்த role-க்கு கிடைத்தால்) சரிபார்க்கவும்.

## 3. Role-wise datasets மற்றும் சோதனைகள்

### 3.1 Super Admin

**மாதிரி users:** Users → Create-ல் ஒவ்வொரு row-ஐயும் தனித்தனியாகச் சேர்க்கவும். `branch_id`-ஐ form-ல் காட்டும் active branch-லிருந்து தேர்வு செய்யவும். QA accounts-க்கு local-only strong password பயன்படுத்தி `must_change_password` இயக்கவும்; Super Admin seed account-ஐ மாற்றவோ நீக்கவோ வேண்டாம்.

| # | Name | Email | Role | Branch |
|---|---|---|---|---|
| 1 | QA Registrar One | `qa.registrar.01@school.test` | Registrar | IGK Main Campus |
| 2 | QA Teacher One | `qa.teacher.01@school.test` | Teacher | IGK Main Campus |
| 3 | QA Accountant One | `qa.accountant.01@school.test` | Accountant | IGK Galle Campus |
| 4 | QA Librarian One | `qa.librarian.01@school.test` | Librarian | IGK Main Campus |
| 5 | QA Guardian One | `qa.guardian.01@school.test` | Guardian | IGK Kandy Campus |

**CRUD/permission checks:** ஒவ்வொரு QA user-ஐ create, edit name/branch, disable/enable செய்து verify செய்யவும். Linked operational data இல்லாத QA user-ஐ மட்டும் delete செய்யவும். Roles & Permissions-ல் `QA Manual Role` என்ற custom role create/update செய்து, பயன்படுத்தப்படாதபோது delete செய்யவும். கடைசி Super Admin assignment அல்லது built-in role-ஐ delete செய்ய முயற்சிக்க வேண்டாம்; பாதுகாப்பு/validation மறுக்க வேண்டும். Audit log-ல் மாற்றங்களைச் சரிபார்க்கவும்.

### 3.2 Institution Admin

**மாதிரி departments:** Departments → Create. Code ஒவ்வொன்றும் unique. Head staff optional; தேவையெனில் seeded staff-ஐத் தேர்வு செய்யவும்.

| # | Name | Code | Active |
|---|---|---|---|
| 1 | QA Languages | `QA-LANG` | Yes |
| 2 | QA Sciences | `QA-SCI` | Yes |
| 3 | QA Arts | `QA-ART` | Yes |
| 4 | QA Computing | `QA-COMP` | Yes |
| 5 | QA Business | `QA-BIZ` | Yes |

**CRUD/permission checks:** 5 department entries create செய்து name மாற்றி update செய்யவும்; பயன்படுத்தப்படாதவற்றை delete செய்து list-ல் இல்லாததை உறுதி செய்யவும். All Campuses-ல் dashboard student/teacher/guardian/class totals பார்க்கவும்; ஒரு campus தேர்வு செய்து totals குறுகிய scope-க்கு மாறுவதை verify செய்யவும். Roles & Permissions-ஐ பார்க்கலாம்; role/permission create/update/delete செய்ய முயற்சித்தால் மறுக்கப்பட வேண்டும். Super Admin-only menu தெரியக்கூடாது.

### 3.3 Registrar

**மாதிரி admissions:** Students → Add Student. ஒவ்வொரு முறையும் seeded class, academic year/program மற்றும் குறைந்தது ஒரு guardian-ஐத் தேர்வு செய்யவும். Class capacity, DOB, admission date, guardian, unique email/document number விதிகளைப் பின்பற்றவும்.

| # | Student | Email | Admission No | DOB | Document (Passport, LK) |
|---|---|---|---|---|---|
| 1 | QA Anika Silva | `qa.student.01@school.test` | `2026-QA-9001` | 2013-04-12 | `QA900001` |
| 2 | QA Ben Perera | `qa.student.02@school.test` | `2026-QA-9002` | 2012-08-23 | `QA900002` |
| 3 | QA Chathuri Fernando | `qa.student.03@school.test` | `2026-QA-9003` | 2014-01-05 | `QA900003` |
| 4 | QA Dilan Jay | `qa.student.04@school.test` | `2026-QA-9004` | 2013-11-18 | `QA900004` |
| 5 | QA Esha Kumar | `qa.student.05@school.test` | `2026-QA-9005` | 2012-06-30 | `QA900005` |

`document_type=passport`, `issuing_country=LK`, `gender`, `nationality`, `phone`, admission date ஆகியவற்றை form விதிப்படி நிரப்பவும். `QA90000x` என்பது fake QA passport value; real identity number பயன்படுத்த வேண்டாம்.

**CRUD/permission checks:** student create/read/edit செய்து phone அல்லது class மாற்றி save verify செய்யவும். Attendance, marks, invoice போன்ற downstream data சேர்ப்பதற்கு முன் மட்டும் delete/withdraw workflow-ஐச் சோதிக்கவும்; linked history இருந்தால் deletion block/soft-delete behavior-ஐ எதிர்பார்க்கவும். Duplicate email/admission number, future DOB/admission date, guardian இல்லாமை, full class ஆகியவற்றில் validation தோல்வியடைய வேண்டும். Finance write permission இல்லை: payment/invoice உருவாக்க முயற்சி மறுக்கப்பட வேண்டும்.

### 3.4 Teacher

Teacher `teacher@school.test`-க்கு ஒதுக்கப்பட்ட class-subject-ஐ மட்டும் தேர்வு செய்யவும். மற்ற teacher-ன் class-ல் அதே செயல்களைச் செய்து 403/permission denial-ஐ verify செய்யவும்.

**மாதிரி attendance rows:** Attendance sheet-ல் அந்த class-subject-இன் roster முழுவதையும் submit செய்ய வேண்டும். கீழுள்ள 5 மாணவர்கள் roster-ல் இல்லாவிட்டால் அவர்களைப் பயன்படுத்த வேண்டாம்; dropdown/roster-ல் உள்ள 5 மாணவர்களுக்கு இந்த status-களைப் பொருத்தவும், மீதமுள்ள roster-ஐயும் sheet-ல் சேர்க்கவும்.

| # | Roster entry | Status | Remarks |
|---|---|---|---|
| 1 | Enrolled Student 1 | present | `QA present` |
| 2 | Enrolled Student 2 | absent | `QA absent` |
| 3 | Enrolled Student 3 | late | `QA late` |
| 4 | Enrolled Student 4 | excused | `QA excused` |
| 5 | Enrolled Student 5 | present | `QA edit check` |

**மாதிரி marks:** திறந்திருக்கும் exam-இல் 0–max marks வரம்புக்குள் உள்ள assigned students-க்கு: `88`, `76.5`, `64`, `91`, `0`. Absent flag-ஐ mark value-உடன் முரண்படாமல் அமைக்கவும்.

**CRUD/permission checks:** Attendance session மற்றும் marks create/edit செய்து summary மாற்றத்தை verify செய்யவும். Attendance edit 48 மணி நேரத்துக்குள் இருக்க வேண்டும்; published results-க்குப் பின் marks lock ஆக வேண்டும். Teacher delete permission இல்லை: attendance/marks delete request மறுக்கப்படுவதை verify செய்யவும். Invalid mark (maximum-ஐ விட அதிகம்), assigned இல்லாத class, incomplete roster ஆகியவை reject ஆக வேண்டும்.

### 3.5 Accountant

Fee Structures → Create-ல் program **அல்லது** class மற்றும் seeded academic year/term தேர்வு செய்யவும். Amount positive, 2 decimal places; currency configured currency list-இல் இருக்க வேண்டும்.

| # | Name | Amount | Currency | Active |
|---|---|---:|---|---|
| 1 | QA Library Fee | 1250.00 | LKR | Yes |
| 2 | QA Lab Fee | 2500.00 | LKR | Yes |
| 3 | QA Activity Fee | 850.00 | LKR | Yes |
| 4 | QA Transport Fee | 4200.00 | LKR | Yes |
| 5 | QA Exam Fee | 1750.00 | LKR | Yes |

**மாதிரி payments:** seeded-ல் இன்னும் balance உள்ள invoices-ஐத் தேர்ந்தெடுக்கவும். Amount invoice balance-ஐ மீறக்கூடாது.

| # | Amount | Method | Reference |
|---|---:|---|---|
| 1 | 100.00 | cash | `QA-CASH-01` |
| 2 | 250.00 | bank_transfer | `QA-BANK-02` |
| 3 | 50.00 | cheque | `QA-CHEQUE-03` |
| 4 | 125.00 | cash | `QA-CASH-04` |
| 5 | 300.00 | bank_transfer | `QA-BANK-05` |

`paid_at` இன்று அல்லது கடந்த தேதி. ஒவ்வொரு payment-க்கும் வேறுபட்ட invoice/போதுமான balance பயன்படுத்தவும்.

**CRUD/permission checks:** Fee Structure create/update செய்து delete-ஐ எந்த invoice-க்கும் பயன்படுத்தப்படாத QA structure-ல் மட்டும் சோதிக்கவும். Invoice/payment ஆகிவிட்ட structure delete reject ஆகலாம். Invoice generate/edit-ஐ அனுமதிக்கப்பட்ட scope-ல் சோதிக்கவும். Payment receipt, invoice balance/status verify செய்யவும். Overpayment rejected; payment-ஐ edit/delete செய்ய முடியாது—payment immutable; correction-க்கு authorized Super Admin reversal workflow மற்றும் காரணம் தேவை.

### 3.6 Librarian

Library → Books → Create. ISBN optional என்றாலும் சேர்த்தால் unique, 10–17 எழுத்துகள் மற்றும் இலக்கங்கள்/`X`/hyphen மட்டுமே. Copies 1–500; publication year 1800–2026.

| # | Title | Author | Category | ISBN | Year | Copies |
|---|---|---|---|---|---:|---:|
| 1 | QA Science Basics | QA Author One | Science | `978-0000000001` | 2022 | 2 |
| 2 | QA World History | QA Author Two | History | `978-0000000002` | 2021 | 1 |
| 3 | QA English Reader | QA Author Three | Languages | `978-0000000003` | 2020 | 3 |
| 4 | QA Number Skills | QA Author Four | Mathematics | `978-0000000004` | 2023 | 2 |
| 5 | QA Art Workbook | QA Author Five | Arts | `978-0000000005` | 2024 | 1 |

**CRUD/permission checks:** ஒவ்வொரு book-ஐ create, title/category/copies update, detail-ல் verify செய்யவும். Active loan/copy relationship இல்லாத QA book-இல் மட்டும் delete சோதிக்கவும். Book edit பிறகு available copy count சரிபார்க்கவும். Invalid ISBN, duplicate ISBN, copies `0`, 2027 year validation-ஐத் தூண்ட வேண்டும். Student/Guardian account-க்கு library write route கிடைக்கக்கூடாது.

### 3.7 Student

Demo `student@school.test` தனது linked student profile-க்கே scope ஆக வேண்டும். Seed data-இல் அந்த account-க்கு தொடர்புடைய தகவலைத் தேர்ந்தெடுத்து கீழே உள்ள 5 read-only checks-ஐச் செய்யவும்:

| # | Sample to inspect | Expected |
|---|---|---|
| 1 | My profile/admission number | தன் profile மட்டும் |
| 2 | Enrolled class/subjects | தன் enrollment மட்டும் |
| 3 | Attendance summary | தன் attendance மட்டும் |
| 4 | Published exam/result/report card | publish செய்யப்பட்டவை மட்டும் |
| 5 | Invoice/payment history | தன் financial records மட்டும் |

**CRUD/permission checks:** Student list/create/update/delete, மற்ற மாணவர் ID/profile, unpublished result, மற்றும் மற்ற மாணவரின் invoice திறக்க முயற்சிக்கவும்; create/update/delete மறுக்கப்பட வேண்டும், பிறர் records-க்கு 403/404 மற்றும் data leakage இல்லாமை எதிர்பார்ப்பு. Student account-க்கு அனுமதிக்கப்பட்ட account/password workflow மட்டும் தனியாகச் சோதிக்கவும்.

### 3.8 Guardian

Demo `guardian@school.test`-இன் linked child-ஐ மட்டும் தேர்ந்தெடுத்து 5 read-only checks:

| # | Sample to inspect | Expected |
|---|---|---|
| 1 | Linked child profile | இணைக்கப்பட்ட child(ren) மட்டும் |
| 2 | Child attendance | linked child data மட்டும் |
| 3 | Published result/report card | வெளியிடப்பட்ட linked-child தகவல் மட்டும் |
| 4 | Child invoice/balance | linked child finance data மட்டும் |
| 5 | Child announcements/documents | linked child/targeted items மட்டும் |

**CRUD/permission checks:** Guardian மற்ற குழந்தையின் profile, direct ID, private document-ஐ திறக்க முயற்சிக்கவும்; access மறுக்கப்பட வேண்டும். Student/guardian/profile/attendance/result/finance create/update/delete actions கிடைக்கக்கூடாது. UI-ல் action மறைந்திருப்பதோடு endpoint-லும் 403/404 வருவதைச் சோதிக்கவும்.

## 4. ஒவ்வொரு role-க்கும் முடிவு பதிவு

| Role | Login + dashboard | 5 sample checks | Create/Update | Delete or guarded behavior | Invalid input | Result/Notes |
|---|---|---|---|---|---|---|
| Super Admin | ☐ | ☐ | ☐ | ☐ | ☐ | |
| Institution Admin | ☐ | ☐ | ☐ | ☐ | ☐ | |
| Registrar | ☐ | ☐ | ☐ | ☐ | ☐ | |
| Teacher | ☐ | ☐ | ☐ | ☐ | ☐ | |
| Accountant | ☐ | ☐ | ☐ | ☐ | ☐ | |
| Librarian | ☐ | ☐ | ☐ | ☐ | ☐ | |
| Student | ☐ | ☐ | N/A — read-only | ☐ | ☐ | |
| Guardian | ☐ | ☐ | N/A — read-only | ☐ | ☐ | |

**SOP acceptance:** role அனுமதித்த operations மட்டும் வெற்றி; அனுமதியற்ற GET/POST/PUT/PATCH/DELETE-கள் server-side-ல் மறுக்கப்பட வேண்டும். Validation தோல்வியில் database unchanged; successful writes refresh-க்குப் பிறகும் இருக்க வேண்டும்; delete/reversal/audit behavior module-ன் data-retention விதிகளுடன் பொருந்த வேண்டும்.
