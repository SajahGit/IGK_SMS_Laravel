# IGK School ERP — Manual Test Database Setup Plan

**Updated:** 2026-11-14
**Target:** `school_management_system_testing_db` on each developer's own local MySQL instance
**Current workspace DB at verification:** `school_management_new` — existing database; do not overwrite or drop as part of this setup.

## Goal and isolation

Create the same synthetic demo starting data for each teammate by running the same committed migrations and seeders against their **own local MySQL server**. A matching schema name on separate laptops does not share data. Do not have the team connect to one shared server/schema unless an explicitly managed shared environment is intended.

Automated tests use in-memory SQLite from `phpunit.xml`; the manual app demo uses the MySQL schema selected in `.env`.

## Setup steps

1. Make a private `.env` for this checkout using `.env.example`:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

   If `.env` already exists, back it up before editing and never distribute it.

2. Set the local connection in `.env` (fill in your own MySQL account):

   ```dotenv
   APP_ENV=local
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=school_management_system_testing_db
   DB_USERNAME=<your-local-mysql-user>
   DB_PASSWORD=<your-local-mysql-password>
   ```

3. In phpMyAdmin connected to your own local MySQL, create the empty schema:

   ```sql
   CREATE DATABASE school_management_system_testing_db
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_unicode_ci;
   ```

4. Verify Laravel's target before any schema/data command:

   ```powershell
   php artisan config:clear
   php artisan config:show database.default
   php artisan config:show database.connections.mysql.database
   ```

   Continue only if the connection is `mysql` and database is `school_management_system_testing_db`.

5. Apply current migrations and local demo seeders:

   ```powershell
   php artisan migrate --seed
   php artisan storage:link
   ```

6. Verify using `php artisan migrate:status`, phpMyAdmin table view, and the documented local login/QA steps in `sample dataset.md`.

## Expected data flow

`DatabaseSeeder` dispatches `ProductionSeeder` only in production; local/testing runs `FullSampleDataSeeder`. The full local chain creates synthetic roles, accounts, academic records, students/staff, fees/invoices/payments, and other demo module data. Production seeding is not a demo-data command.

`migrate --seed` writes only to the DB selected by the current `.env`. It does not import records from `school_management_new` or synchronize user-created changes across team members.

## Destructive command warning

`php artisan migrate:fresh --seed` deletes every table and row in the selected database before recreating and seeding it. Only use it after checking the exact database name and confirming that the DB is your disposable local test schema. Never run it against `school_management_new`, any shared database, or production. The team has not run or authorized a cleanup of the existing databases.

For the detailed current DB snapshot and phpMyAdmin-safe cleanup guidance, see `README-SETUP.md`.
