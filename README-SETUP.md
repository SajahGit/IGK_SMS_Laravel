# School Management System (Laravel + MySQL) - Complete MVP code

Modules: Admin, Teacher, Student, Parent, Reports. Roles: admin, teacher, student, parent.
Stack: Laravel 11 or newer (PHP 8.2+), MySQL (XAMPP), Blade + Bootstrap 5.
UI: glassmorphism design with dark and light theme (toggle button in the top bar), responsive for phones. Bootstrap, Bootstrap Icons and the Inter font are bundled in `public/vendor`, so no internet is needed.

This folder contains ONLY our own files (no `vendor`, no framework). You put it on top of a fresh Laravel project.

## 1. Setup (XAMPP, Windows)

1. Start **Apache** and **MySQL** in XAMPP. Open phpMyAdmin and create an empty database named `igk_school_db`
   (collation utf8mb4_unicode_ci).
2. In a terminal (inside htdocs or any folder):
   ```
   composer create-project laravel/laravel school-management
   cd school-management
   ```
3. Copy everything from this folder (including the `public` folder with the theme) INTO the `school-management` project folder and choose **Replace / overwrite** when asked
   (it replaces `bootstrap/app.php`, `routes/web.php`, `app/Models/User.php`, `database/seeders/DatabaseSeeder.php`).
4. Open `.env` and set:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=igk_school_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```
5. Run:
   ```
   php artisan migrate --seed
   php artisan serve
   ```
6. Open http://127.0.0.1:8000 and log in.

To reset all demo data at any time: `php artisan migrate:fresh --seed`

> Optional: delete `tests/Feature/ExampleTest.php` (the default test expects the old welcome page).

## 2. Demo accounts (password for all: `password`)

| Role | Email |
|---|---|
| Admin | admin@school.test |
| Teacher | teacher@school.test (Maths, Science) / teacher2@school.test (English, ICT) |
| Student | student@school.test (linked to parent@school.test) |
| Parent | parent@school.test |

The seeder also creates 2 classes (Grade 10 A and B), 4 subjects, 10 more students with parents
(student2@school.test ... student11@school.test, parent2@school.test ...), 14 days of attendance and 3 exams per class with marks.

## 3. What each role can do

- **Admin**: dashboard counts; CRUD for students, teachers, parents, classes, subjects; assign subject + teacher to class; link a student to a parent (student edit page); all reports.
- **Teacher**: dashboard; my classes and students; mark attendance (bulk, editable); attendance history; create exams; enter marks (bulk); exam result summary; reports for own classes only.
- **Student**: dashboard; profile (edit name, phone, password); attendance with month filter; results with grade and average.
- **Parent**: sees linked children; attendance and results of each child (cannot open other children).
- **Reports**: student list, attendance (below 75% highlighted), results with rank; print and CSV download.

Grades: A 75+, B 65-74, C 55-64, S 40-54, F below 40. Late counts as attended.

## 4. File ownership (for the Git team)

| Person | Branch | Files |
|---|---|---|
| Alass | `feature/admin-parent` | `app/Http/Controllers/Admin/*`, `app/Http/Controllers/Guardian/*`, `resources/views/admin/*`, `resources/views/guardian/*`, `routes/admin.php`, `routes/guardian.php` |
| Sumaiya | `feature/teacher` | `app/Http/Controllers/Teacher/*`, `resources/views/teacher/*`, `routes/teacher.php` |
| Rajeeya | `feature/student` | `app/Http/Controllers/Student/*`, `resources/views/student/*`, `routes/student.php` |
| Hasfa | `feature/reports` | `app/Http/Controllers/Report/*`, `resources/views/reports/*`, `routes/reports.php`, `docs/*` |
| Foundation (Alass, first, on `main`) | `main` | `bootstrap/app.php`, `routes/web.php`, `app/Http/Middleware/EnsureRole.php`, `app/Support/SchoolHelper.php`, `app/Models/*`, `database/migrations/*`, `database/seeders/*`, `resources/views/layouts/*`, `resources/views/auth/*`, `resources/views/errors/*`, `app/Http/Controllers/Auth/*`, `public/css/glass.css`, `public/js/app.js`, `public/vendor/*` |

Shared partials used by several modules: `layouts/head`, `layouts/stat` (icon stat card), `layouts/errors`, `layouts/attendance-table` (student + parent), `layouts/results-table` (student + parent).
Merge order: Admin/Parent, Teacher, Student, Reports.

## 5. Folder map

```
app/Http/Controllers/{Auth,Admin,Teacher,Student,Guardian,Report}
app/Http/Middleware/EnsureRole.php        role:admin / role:admin,teacher
app/Models/                               User, SchoolClass, Subject, Teacher, Guardian, Student, ClassSubject, Attendance, Exam, Result
app/Support/SchoolHelper.php              attendance %, grades, averages, teacher-class checks (shared)
database/migrations/                      10 tables (classes table = SchoolClass model)
database/seeders/DatabaseSeeder.php       demo data
resources/views/{layouts,auth,admin,teacher,student,guardian,reports}
routes/{web,admin,teacher,student,guardian,reports}.php
```

## 6. Troubleshooting

- `Access denied for user` / `Unknown database`: check the `.env` DB settings and that the database exists.
- `could not find driver`: enable `extension=pdo_mysql` in XAMPP's php.ini, restart Apache.
- Page looks unstyled: open the site through `php artisan serve` (the `public` folder must be the web root) and make sure the `public/css`, `public/js` and `public/vendor` folders were copied into the Laravel project.
- 403 page: you are logged in with a different role than the page needs.
- After pulling new code: `php artisan migrate` and, if routes or views look stale, `php artisan optimize:clear`.

## 7. UI theme (glassmorphism)

- `public/css/glass.css`: all styling (frosted glass cards, gradient background, sidebar, tables, forms, buttons). Change the accent colours at the top (`--accent-1`, `--accent-2`, `--accent-3`).
- `public/js/app.js`: dark/light toggle (remembered in the browser), mobile sidebar, auto-hide success messages.
- `resources/views/layouts/app.blade.php`: sidebar menu per role with icons. To add a menu item, add one line to the `$menus` array.
- `resources/views/layouts/stat.blade.php`: stat card. Use `@include('layouts.stat', ['icon' => 'people-fill', 'value' => 10, 'label' => 'Students', 'tone' => 'indigo'])` (tones: indigo, cyan, green, amber, rose, violet; icon names from https://icons.getbootstrap.com).
- Custom error pages: `resources/views/errors/403, 404, 419, 500`.
- Printing hides the glass effects and the menu automatically.
