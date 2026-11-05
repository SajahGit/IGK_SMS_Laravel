# IGK School ERP — team setup after ZIP extraction

This guide is for a developer's local copy. Do not copy a shared `.env`, database file, `vendor`, `node_modules`, or log files into the team ZIP.

See [README-SETUP.md](./README-SETUP.md) for the full Tamil step-by-step setup, role walkthrough, queue/scheduler instructions, test commands, and UAT checklist.

## Requirements

- PHP 8.2+ with `bcmath`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_mysql`, `pdo_sqlite`, `tokenizer`, `xml`, and `zip`
- Composer 2
- Node.js 20.19+ and npm
- MySQL 8 for a per-developer database, or SQLite for isolated local work

## After extracting the ZIP on Windows

1. Extract the project to a writable folder, for example `C:\work\igk-school-erp`.
2. Open PowerShell in that folder and run:

   ```powershell
   .\setup-team.bat
   ```

   The script installs PHP and frontend dependencies, creates `.env` from `.env.example` if needed, generates a new application key for a new `.env`, and builds the frontend assets. It does not run migrations or connect to a database.

3. Configure `.env` for a database owned by this developer. For MySQL, first create a separate database such as `igk_erp_<name>_dev`, then set:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=igk_erp_<name>_dev
   DB_USERNAME=<your-local-db-user>
   DB_PASSWORD=<your-local-db-password>
   ```

   Never point local work at the shared `archi` or production database. Never send `.env` or database credentials in the ZIP or team chat.

4. If using the default SQLite configuration, create the local database file if it is missing:

   ```powershell
   if (!(Test-Path database\database.sqlite)) {
       New-Item -ItemType File -Path database\database.sqlite | Out-Null
   }
   ```

5. Create the local schema and demo data:

   ```powershell
   php artisan migrate --seed
   php artisan storage:link
   ```

   `DatabaseSeeder` creates demo records. Do not use demo seeding in production; deployment uses the documented `ProductionSeeder` procedure.

6. Start the application:

   ```powershell
   php artisan serve
   ```

   Open `http://127.0.0.1:8000`. Demo credentials are listed in [README-SETUP.md](./README-SETUP.md); rotate any demo password before real use.

7. Confirm the installation:

   ```powershell
   php artisan test
   php vendor\bin\pint --test
   ```

## Role-specific code layout

Keep changes in the matching user area; shared rules and database changes stay in shared folders:

```text
app\Http\Controllers\Admin\       resources\views\admin\
app\Http\Controllers\Registrar\   resources\views\registrar\
app\Http\Controllers\Teacher\     resources\views\teacher\
app\Http\Controllers\Student\     resources\views\student\
app\Http\Controllers\Guardian\    resources\views\guardian\
app\Http\Controllers\Accountant\  resources\views\accountant\
app\Http\Requests\Accountant\
app\Http\Controllers\Report\      resources\views\reports\
app\Http\Controllers\Auth\        resources\views\auth\
routes\modules\admin.php          routes\registrar.php, accountant.php, teacher.php, student.php, guardian.php, reports.php
app\Models\, Policies\, Services\, Support\, Observers\
database\migrations\, seeders\
tests\Feature\, tests\Unit\
```

Audit records are written to `audit_logs`; application errors are written under `storage\logs`. Do not edit or share these logs as source files.

## ZIP contents and cleanup

Include source, `composer.json`/`composer.lock`, `package.json`/`package-lock.json`, `.env.example`, setup instructions, and tests. Exclude `.env*` secrets/backups (except the example templates), `database\database.sqlite`, `vendor`, `node_modules`, `public\build`, `storage\logs`, and generated framework caches. Superseded reports are retained under `docs\archive\`; include that folder only when historical records are needed in the handover.

## Safe local reset

Use `php artisan migrate:fresh --seed` only against your own disposable development database. It deletes every table in the configured database. Verify the database name in `.env` before running it.
