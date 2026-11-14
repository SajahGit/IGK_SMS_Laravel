# IGK School ERP Environment Configuration

This guide describes application environment settings without containing real credentials. Use `.env.example` as the local template. Each developer keeps a private `.env`, unique `APP_KEY`, and local database login; never commit or share the `.env`.

## Local development and team testing

The current team test schema name is `school_management_system_testing_db`. Each developer may use that same schema name on their **own local MySQL server**. Do not connect all developers to the same database unless a separately managed shared environment is intended.

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Colombo

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_management_system_testing_db
DB_USERNAME=<your-local-mysql-user>
DB_PASSWORD=<your-local-mysql-password>
```

`.env.example` also sets local log, cache, session, queue, filesystem and mail-to-log defaults. Set `APP_KEY` using `php artisan key:generate`; do not reuse another developer's key.

Before any migration/seeding command:

```powershell
php artisan config:clear
php artisan config:show database.default
php artisan config:show database.connections.mysql.database
```

Run `php artisan migrate --seed` only after confirming the local target name. Automated tests use `phpunit.xml`'s in-memory SQLite and do not require the MySQL demo schema.

## Workspace snapshot (2026-11-14)

The checked workspace `.env` selected local MySQL `127.0.0.1:3306/school_management_new`. That database already contained data and had two current migrations pending. The requested `school_management_system_testing_db` schema was not present on the inspected server at the time. This snapshot may become stale; inspect the actual private `.env` and config output before executing commands. Do not use this documentation snapshot as permission to alter or delete either database.

## Production principles

- `APP_ENV=production` and `APP_DEBUG=false`.
- Use a unique production `APP_KEY` and a dedicated least-privilege database account.
- Store database, SMTP, SMS and payment-gateway secrets in the deployment secret manager or protected environment configuration, never Git or documentation.
- Configure HTTPS, secure session cookies, production queue/cache/filesystem settings and monitoring before release.
- Do not use local demo credentials or run local demo seeders in production. Follow `docs/guides/deploy.md`.

## Sensitive configuration

Document variable names and purpose, never secret values. If an actual credential was committed or shared, rotate it and remove it from tracked files/history using the repository's approved secret-response process.
