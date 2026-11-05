# IGK ERP File Cleanup Report

Superseded status reports were moved to `docs/archive/`; they were not deleted. The authoritative current references remain in the project root and `docs/`.

The application source, migrations, seeders, configuration, tests, and current setup/compliance guides were retained. No production database changes or destructive file deletions were performed.

Exclude these generated/local items from the team ZIP:
- `vendor/` (reinstall with Composer)
- `node_modules/` (reinstall with npm)
- `storage/logs/` and generated framework caches
- `.env` and any other secrets (use `.env.example`)
- `database/database.sqlite` and other local database files
- `public/build/` (regenerate with `npm run build`)
