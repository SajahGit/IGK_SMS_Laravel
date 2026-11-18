# IGK School ERP — Manual Test Database Safety Review

**Updated:** 2026-11-14
**Approved local test schema name:** `school_management_system_testing_db`
**Existing workspace schema observed:** `school_management_new` (not to be reset or dropped by this guide)

## Findings

- The current `.env` at inspection time selected MySQL at `127.0.0.1:3306/school_management_new`, not the SQLite values pasted into chat.
- The existing schema had 10,017 `users` rows and 64 applied migrations; the two newest migrations were pending.
- `school_management_system_testing_db` was not present in the inspected server schema list.
- `phpunit.xml` configures `APP_ENV=testing` and in-memory SQLite, so the automated suite is isolated from the local MySQL app DB.
- `php artisan db:show --counts` failed because this MySQL setup lacks `performance_schema.session_status`; that command failure is not proof the application database is missing. Use config output, `migrate:status`, and phpMyAdmin.

## Safe commands for the new local test database

Create the target schema in phpMyAdmin (on your own local server):

```sql
CREATE DATABASE school_management_system_testing_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Set that database in your private `.env`, then verify before any migration or seeding:

```powershell
php artisan config:clear
php artisan config:show database.default
php artisan config:show database.connections.mysql.database
php artisan migrate:status
```

Proceed only when the connection is `mysql` and the selected DB is exactly `school_management_system_testing_db`:

```powershell
php artisan migrate --seed
php artisan storage:link
```

Each teammate should create the same-named schema on their own local MySQL instance and run the same seeders. Do not share one database or `.env` unless a shared development server was explicitly chosen and separately protected.

## Reset and deletion safeguards

`php artisan migrate:fresh --seed` is destructive: it removes all tables/data from the configured database. Never run it until you verify that `.env` selects your disposable local test schema. Never run it against `school_management_new`, a shared DB, or production.

Unknown phpMyAdmin schemas are not automatically unused. Identify owner and application, export a backup, verify the target DB works, and only then manually drop a single confirmed-unused schema. Do not use wildcard/loop/bulk DROP commands. Never delete MySQL system schemas (`information_schema`, `mysql`, `performance_schema`, `sys`) or phpMyAdmin metadata unless you understand the consequences. This work has not deleted or seeded any database.

This guide supersedes the older 2026-11-02 version, which named different databases and described an earlier migration/seeder state. Follow current `README-SETUP.md` and the current application migrations/seeders.
