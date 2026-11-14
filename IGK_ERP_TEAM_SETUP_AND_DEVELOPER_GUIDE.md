# IGK ERP Team Setup and Developer Guide

## Team

Team members: Alas, Shilfa, Rajiya, Hasfa, Sumaiya. Module ownership and agreed work split are listed in [IGK_ERP_TEAM_MODULE_OWNERSHIP_MATRIX.md](./IGK_ERP_TEAM_MODULE_OWNERSHIP_MATRIX.md).

## New developer setup

1. Prefer Git fork/clone over ZIP so you can pull upstream changes and push feature branches. Full Windows instructions are in [README-SETUP.md](./README-SETUP.md).
2. Run `.\setup-team.bat`. It installs dependencies and builds frontend assets.
3. Use your own `.env` and local MySQL database named `school_management_system_testing_db`. If creating `.env` manually, run `Copy-Item .env.example .env` and `php artisan key:generate`; update your local MySQL username/password in `.env` only. Never share `.env` or its credentials.
4. Create that schema on your own local MySQL server, verify the configured target with `php artisan config:show database.connections.mysql.database`, then run `php artisan migrate --seed` and `php artisan storage:link`. `DatabaseSeeder` loads the same synthetic demo dataset for each teammate.
5. Start with `php artisan serve`, then use the local demo accounts documented in [sample dataset.md](./sample%20dataset.md).

The same DB name is safe only when each developer has a separate local MySQL instance. Do not have the whole team connect to one shared server/schema unless intentionally coordinating a shared environment. Never use demo seeding, demo credentials, or a production database for production work. `php artisan migrate:fresh --seed` is destructive; use it only after confirming `.env` points to your own disposable local database. The longer setup, current DB snapshot and safe phpMyAdmin cleanup procedure are in [README-SETUP.md](./README-SETUP.md).

## Git collaboration

- Create one feature branch per assigned module; do not work directly on `master`.
- Keep each PR focused on the assigned module and include relevant tests.
- Shared routes, permissions, common models/services, config, migrations, and shared layouts can affect multiple owners. Coordinate before editing the same file; request review from the designated shared-file owner in the ownership matrix.
- Pull/merge upstream `master` regularly. Do not send `.env`, local database files, `vendor`, `node_modules`, logs, or generated caches to Git/ZIP/team chat.

## Current code areas

- Admin and authentication: `app/Http/Controllers/InstitutionAdmin/`, `app/Http/Controllers/SuperAdmin/`, `app/Http/Controllers/Auth/`
- Student/guardian: `app/Http/Controllers/Student/`, `app/Http/Controllers/Guardian/`, `app/Models/`
- Teacher/academic: `app/Http/Controllers/Teacher/`, `app/Http/Controllers/Registrar/`
- Finance: `app/Http/Controllers/Accountant/`, `app/Http/Controllers/Accounting/`
- Reports: `app/Http/Controllers/Report/`
- Cross-module work: `app/Models/`, `app/Policies/`, `app/Services/`, `app/Support/`, `app/Observers/`, `routes/`, `database/migrations/`, `database/seeders/`, `tests/`
- Views are grouped under the corresponding existing directories in `resources/views/`.

Use the existing routes, controllers, models, and view naming before creating new folders. For setup, demo logins, and reset safety, follow [README-SETUP.md](./README-SETUP.md) and [sample dataset.md](./sample%20dataset.md).
