# School Management System (Laravel 12)

Roles: **Admin, Teacher, Student, Parent** + Reports. One project, one login page, same UI everywhere.

## Run it (3 steps)

You need: **PHP 8.2 or newer** (XAMPP 8.2+ is fine) and **Composer**. Nothing else - no Node, no database setup.

1. Open a terminal / CMD **inside this folder**.
2. Choose ONE:
   - **`setup-clean.bat`** - empty system, only the admin account. **Use this to enter your own real data.**
   - **`setup.bat`** - same, but with demo data (30 students, 4 teachers, one published exam) for testing.
   (Mac/Linux: `./setup-clean.sh` or `./setup.sh`)
3. The browser opens at **http://127.0.0.1:8000** - log in.

Next time you only need `start.bat` (or `php artisan serve`).

> The first time can take a few minutes because Composer downloads Laravel.
> If `php` or `composer` is "not recognized": in XAMPP add `C:\xampp\php` to the Windows PATH and install Composer from getcomposer.org.

## If something fails

| Message | Fix |
|---|---|
| `php` / `composer` not recognized | Add `C:\xampp\php` to the Windows PATH, install Composer from getcomposer.org, open a NEW CMD window |
| `could not find driver` | Open `C:\xampp\php\php.ini`, remove the `;` before `extension=pdo_sqlite` and `extension=sqlite3`, restart CMD (or use the MySQL option below) |
| Composer says an extension is missing (zip, mbstring, openssl, fileinfo, curl) | Remove the `;` before that extension line in the same `php.ini` |
| PHP version is too old | Laravel 12 needs PHP 8.2+. Install the latest XAMPP |
| Page looks unstyled | Run `npm install` then `npm run build` once (needs Node.js) |

## Logins (password for all = `password`)

| Role | Email |
|---|---|
| Admin | admin@school.test |
| Teacher | teacher@school.test |
| Student | student@school.test |
| Parent | parent@school.test |

Demo data: 2026 / Term 3, Grade 10 A and B, 6 subjects, 4 teachers, 30 students, one published "Mid Term Exam".
To reset everything: `php artisan migrate:fresh --seed`

## Delete all data and start fresh

- `reset-clean.bat` - deletes EVERYTHING, keeps only `admin@school.test` / `password`.
- `reset-demo.bat` - deletes everything and loads the demo data again.
(or by hand: `php artisan migrate:fresh --seed --seeder=AdminOnlySeeder`)

Then add your data in this order (each step needs the one before it):

| # | Admin menu | What to add |
|---|---|---|
| 1 | Academic Years | the year, tick "current" |
| 2 | Terms | the term, pick the year, tick "current" |
| 3 | Classes | e.g. Grade 10 |
| 4 | Sections | A, B, capacity |
| 5 | Subjects | Maths, Science ... |
| 6 | Teachers | teacher accounts (email = login) |
| 7 | Class Subjects | section + subject + teacher |
| 8 | Students | place each student in a section |
| 9 | Parents | link to their children |

Leave the password empty when creating an account and it becomes `password`.
"Cannot delete ... other records use it" is a safety rule: remove the records that use it first (or edit instead of deleting).

## Use MySQL (XAMPP) instead of SQLite

1. Start MySQL in XAMPP, open phpMyAdmin, create an empty database `school_management`.
2. In `.env` put `#` in front of `DB_CONNECTION=sqlite` and remove the `#` from the 6 MySQL lines.
3. Run `php artisan migrate:fresh --seed` then `php artisan serve`.

## Demo script (the core MVP)

1. **Admin:** Dashboard -> Teachers -> add one -> Students -> add one to a section -> Parents -> add one linked to that student -> Class subjects -> assign a teacher.
2. **Teacher:** Attendance -> take attendance -> Save. Exams -> New exam -> add your subject -> Enter marks -> Publish results.
3. **Student:** My attendance, My results, report card (print).
4. **Parent:** My children -> attendance and results.
5. **Reports (admin/teacher):** 4 reports, each with Download CSV.
6. **Security:** teacher opens `/admin/dashboard` -> 403. Student changes the id in the URL -> 403.

Automatic check: `php artisan test` (opens every page for every role and runs the exam flow).

## Team work: base project + one package per person

This project is the **base**. It already has everything that is shared, so every person can run and test their own part alone:
login, layout and UI parts, **all database tables and models**, demo data, `GradingService`, `StudentReportService`, shared attendance/results views.

Each person then pastes **only their own package** (a small zip with `app/`, `resources/`, `routes/` folders) into the project:

| Person | Package | What it adds | Test with |
|---|---|---|---|
| **Alass** | `01-Alass-Admin-Parent.zip` | Admin module + Parent portal | admin@school.test, parent@school.test |
| **Sumaiya** | `02-Sumaiya-Teacher.zip` | Teacher: attendance, exams, marks, publish | teacher@school.test |
| **Rajeeya** | `03-Rajeeya-Student.zip` | Student: dashboard, profile, attendance, results | student@school.test |
| **Hasfa** | `04-Hasfa-Reports.zip` | Reports + CSV + printable report card | admin@school.test, student@school.test |

Until a module is pasted, its role sees "module not installed yet" after login. That is normal.
Step-by-step GitHub commands (fork, branch, paste, push, pull request) are in `TEAM-GUIDE.md`.

Rules: edit **only your own files**. Need a new table or a change in a shared file (`routes/web.php`, `config/school.php`, `resources/views/components`, `app/Models`)? Tell the group first. New table = new migration (`php artisan make:migration`), never edit an old one.

If you add new Tailwind classes to a page, rebuild the CSS once: `npm install` then `npm run build` (needs Node.js). Compiled CSS is already included.

## Where things are

- `config/school.php` - roles, grade scale (A>=75, B>=65, C>=55, S>=40), attendance rules
- `app/Models` - all tables as models
- `app/Services/GradingService.php` - marks -> grades -> GPA (runs on "Publish results")
- `resources/views/components` - shared layout and UI parts (`<x-layout>`, `<x-ui.table>`, `<x-ui.field>` ...)
- `database/migrations` + `database/seeders` - tables and demo data

## Documentation (last, Hasfa)

Requirements, module descriptions, roles/permissions table, ER diagram (18 tables), screenshots, install guide (this file), test cases (demo script + `php artisan test`), team contribution.
