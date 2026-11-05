# IGK ERP Team Setup and Developer Guide

## Team
Leaders: Alas, Shilfa. Members: Rajiya, Hasfa, Sumaiya.

## Setup (Windows, after ZIP extraction)

Follow [README-SETUP.md](./README-SETUP.md) for the complete Tamil step-by-step guide, or [README-SETUP.md](README-SETUP.md) for the concise setup. In short: run `setup-team.bat`, configure `.env` to use a developer-owned database such as `igk_erp_<name>_dev`, then run `php artisan migrate --seed`, `php artisan storage:link`, and `php artisan serve`.

The setup script does not migrate or seed a database. Never use the shared `archi` or production database for development. Do not distribute `.env`, database files, application logs, `vendor`, or `node_modules`.

## Git/Fork prohibited. Local handover only.

## Demo accounts

The local demo credentials, including the correct Super Admin password, are listed in [README-SETUP.md](./README-SETUP.md). Do not use demo credentials in production.

## Docs
See docs/ and root README.md.

## Role folder ownership

- Super Admin: `app/Http/Controllers/SuperAdmin/`, `resources/views/super-admin/` (role and permission management)
- Institution Admin: `app/Http/Controllers/InstitutionAdmin/`, `resources/views/institution-admin/`, shared admin routes in `routes/modules/admin.php`
- Registrar: `app/Http/Controllers/Registrar/`, `resources/views/registrar/`, `routes/registrar.php`
- Teacher: `app/Http/Controllers/Teacher/`, `resources/views/teacher/`, `routes/teacher.php`
- Student: `app/Http/Controllers/Student/`, `resources/views/student/`, `routes/student.php`
- Accountant: `app/Http/Controllers/Accountant/`, `resources/views/accountant/`, `routes/accountant.php`
- Guardian: `app/Http/Controllers/Guardian/`, `resources/views/guardian/`, `routes/guardian.php`
- Reports: `app/Http/Controllers/Report/`, `resources/views/reports/`, `routes/reports.php`
- Shared auth, authorization, data, and audit work belongs in `app/Http/Controllers/Auth/`, `app/Policies/`, `app/Models/`, `app/Services/`, `app/Support/`, `app/Observers/`, and their tests.
