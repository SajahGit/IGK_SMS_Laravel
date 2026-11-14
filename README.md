# IGK School ERP

Laravel-based school management ERP (admissions, academics, marks and results, finance, reports).

## Quick start
See [README-SETUP.md](./README-SETUP.md) for Windows Git/ZIP setup, `.env` creation, local demo seeding, and run instructions. Team module ownership is in [IGK_ERP_TEAM_MODULE_OWNERSHIP_MATRIX.md](./IGK_ERP_TEAM_MODULE_OWNERSHIP_MATRIX.md); demo logins and role-wise QA data are in [sample dataset.md](./sample%20dataset.md).

## Documentation
- `docs/guides/` - install, environment, deploy, backup and role guides
- `docs/decisions/` - architecture decision records (ADR-001 to ADR-004)
- `docs/architecture.md`, `docs/database/schema.sql`, `docs/known-issues.md`

## Tests
```powershell
php artisan test
vendor\bin\pint --test
npm run build
```
