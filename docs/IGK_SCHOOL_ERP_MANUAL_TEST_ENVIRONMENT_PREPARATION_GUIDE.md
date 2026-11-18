# IGK School ERP — Manual Test Environment Preparation

**Updated:** 2026-11-14
**Purpose:** Set up an isolated local MySQL demo environment without altering the existing workspace database.

## Active workspace snapshot

Read-only inspection on 2026-11-14 found the current workspace `.env` configured for `mysql` at `127.0.0.1:3306`, database `school_management_new`. That schema had 10,017 rows in `users`, 64 applied migrations, and two pending migrations. The requested `school_management_system_testing_db` schema was not listed on the local server then. Configuration can change; re-check before commands.

This is a snapshot, not permission to migrate, seed, reset, or delete `school_management_new`. No database rows were changed by preparing these docs.

## Safe local manual-test setup

1. Ensure local MySQL 8 and PHP `pdo_mysql` are available.
2. Create your own `.env` from `.env.example`; generate a unique key and set your own local MySQL credentials.
3. Use `DB_DATABASE=school_management_system_testing_db`.
4. In phpMyAdmin on your own local server create the UTF-8 schema if it does not exist:

   ```sql
   CREATE DATABASE school_management_system_testing_db
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_unicode_ci;
   ```

5. Verify connection/database:

   ```powershell
   php artisan config:clear
   php artisan config:show database.default
   php artisan config:show database.connections.mysql.database
   ```

6. Only if the verified target is the test DB, create its schema and synthetic demo rows:

   ```powershell
   php artisan migrate --seed
   php artisan storage:link
   ```

7. Start the app using `php artisan serve`; follow `sample dataset.md`.

## Team data behavior

Same seeders on separate local MySQL installations create equivalent starting demo datasets. They do **not** make teammates' subsequent changes synchronize. Do not point all teammates at one shared DB unless the team intentionally wants one coordinated shared environment and has agreed access, backup and reset policies.

Automated `php artisan test` uses in-memory SQLite from `phpunit.xml`; it is separate from the local MySQL demo DB.

## phpMyAdmin deletion safety

Do not delete schemas just because their names are unfamiliar. First identify their owner/purpose and export a backup. Keep `school_management_new` and all unknown schemas until the new test DB is migrated, seeded and verified, and you have explicitly decided what can be removed.

For a confirmed unused schema only, select it in phpMyAdmin → **Operations** → **Drop the database**. Or run a single explicit statement after replacing the name with one you have verified:

```sql
DROP DATABASE `replace_with_confirmed_unused_database_name`;
```

Never bulk-drop databases. Never drop `information_schema`, `mysql`, `performance_schema`, or `sys`; do not drop `phpmyadmin` unless you understand the consequences. If uncertain, do not delete it. No schema has been deleted by this work.
