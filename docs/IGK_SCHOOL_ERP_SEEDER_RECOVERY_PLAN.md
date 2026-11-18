# IGK School ERP — Local Seeder and Recovery Guide

**Updated:** 2026-11-14
**Local test database:** `school_management_system_testing_db` on each developer's own MySQL instance

## Current seeder behavior

`database/seeders/DatabaseSeeder.php` dispatches `ProductionSeeder` only for `production`; `local` and `testing` dispatch the full `FullSampleDataSeeder`; other environments are rejected. The full demo seeder is intended for a fresh local/testing schema. The old 2026-11-02 notes below described an earlier state and a different database; they are superseded by this current guide.

## Safe setup

1. Create a private `.env` from `.env.example` and generate a unique key.
2. Set `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, and `DB_DATABASE=school_management_system_testing_db`; enter only your own local MySQL username/password.
3. Create that schema on your own local server if it does not exist.
4. Verify the selected DB before any schema/data command:

   ```powershell
   php artisan config:clear
   php artisan config:show database.default
   php artisan config:show database.connections.mysql.database
   ```

5. Continue only if the connection is `mysql` and the selected DB name is exactly `school_management_system_testing_db`.
6. On a new schema run:

   ```powershell
   php artisan migrate --seed
   ```

7. Verify migrations with `php artisan migrate:status`, then inspect demo logins and module records using `sample dataset.md`.

## Rerun/reset guidance

Do not assume every seeder is safe to rerun or idempotent. On an already seeded database, inspect that seeder and its tests first. To recreate an entirely disposable local test dataset only, `php artisan migrate:fresh --seed` drops all tables/rows in the currently selected database. Re-verify the exact target before using it. Never run on `school_management_new`, shared databases, production, or any database whose contents you need.

## Team behavior

Same migrations and seeders on separate local MySQL instances create the same starting sample dataset. They do not synchronize later developer-entered changes. Do not copy `.env`, MySQL credentials, real records, or unreviewed database dumps between teammates.

The current workspace was read-only inspected on 2026-11-14; its existing DB was `school_management_new`, with existing user data and two pending migrations. No seed/reset/cleanup command was run as part of this documentation update. Full connection snapshot and phpMyAdmin safety steps are in `README-SETUP.md`.
