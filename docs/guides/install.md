# Installation Guide (install.md)

**Project:** IGK School ERP — Laravel 12.70.0  
**SOP Reference:** §29 Handover Artefact #4  
**Owner:** D6  

---

## Overview

Local setup from zero to a seeded working app. Verified by a person who has never run it before.

---

## Prerequisites

- PHP 8.3+ with extensions: bcmath, ctype, curl, dom, fileinfo, gd, mbstring, openssl, pdo_mysql, tokenizer, xml, zip
- Composer 2.x
- Node.js 20.19+ and npm
- MySQL 8.0 (or SQLite for local development)
- Git

---

## Step 1: Clone the repository

```bash
git clone <repository-url> sms
cd sms
```

> Note: Per project constraints, Git is not initialized in this working copy. In production, the repository is transferred to the client organization with `v1.0.0` tagged.

---

## Step 2: Install PHP dependencies

```bash
composer install --optimize-autoloader --no-interaction
```

---

## Step 3: Install JavaScript dependencies and build assets

```bash
npm ci
npm run build
```

On Windows, `setup-team.bat` performs these dependency and asset steps without running migrations. See [README-SETUP.md](../../README-SETUP.md).

---

## Step 4: Configure environment

```bash
cp .env.example .env
```

Edit `.env` with your local database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms_local
DB_USERNAME=root
DB_PASSWORD=

APP_URL=http://localhost
APP_DEBUG=true
```

Generate the application key:

```bash
php artisan key:generate
```

---

## Step 5: Create the database

```bash
mysql -u root -p -e "CREATE DATABASE sms_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

## Step 6: Run migrations and seeders

```bash
php artisan migrate --seed
```

This runs all migrations and the `DatabaseSeeder`, which creates:
- Roles and permissions (Spatie RBAC)
- Academic year 2026 and Term 1
- Grade 10 sections A/B, subjects, teacher assignments, and timetable
- Two teachers, demo administrator/registrar/accountant accounts, 11 students, and linked guardians
- Attendance records, exams, and results
- Fee structures, invoices, and mixed-status payments
- An announcement and grade scale
- Super Admin account (`superadmin@school.test`, password `password` (local/demo; production seeds `ChangeMe123!` and forces a change))

This is the current small local demo dataset, not the SOP's full 240-student `DemoDataSeeder` or 5,000-student performance dataset; those dedicated seeders are not present yet.

---

## Step 7: Link storage

```bash
php artisan storage:link
```

---

## Step 8: Serve the application

```bash
php artisan serve
```

Visit `http://localhost:8000`.

---

## Step 9: First login

Log in as Super Admin:
- Email: `superadmin@school.test`
- Password: `password` (local/demo; production seeds `ChangeMe123!` and forces a change)

You will be forced to change the password on first login.

---

## Verification Checklist

After installation, confirm:

- [ ] Home page returns 200
- [ ] Super Admin can log in and is redirected to password change
- [ ] After password change, dashboard is accessible
- [ ] All roles visible in user management
- [ ] Reports load without errors
- [ ] Test suite passes: `php artisan test`

---

## Troubleshooting

| Issue | Solution |
|---|---|
| `pdo_sqlite` missing | Install `php-sqlite` extension or use MySQL |
| `APP_KEY` not set | Run `php artisan key:generate` |
| Storage link broken | Run `php artisan storage:link` |
| Migration fails | Ensure database exists and credentials are correct |
| Permission denied on storage/ | `chmod -R 775 storage bootstrap/cache` |
