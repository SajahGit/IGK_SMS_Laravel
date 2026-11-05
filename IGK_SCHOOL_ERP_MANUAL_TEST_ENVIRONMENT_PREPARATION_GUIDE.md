# IGK SCHOOL ERP - MANUAL TESTING ENVIRONMENT PREPARATION GUIDE

**Generated:** 2026-11-02  
**Project Path:** E:\Laravel-Projects\Architectured Final S  
**Purpose:** Safe setup of separate manual testing database without affecting production/development archi DB  

## 1. CURRENT CONFIGURATION (VERIFIED)
- .env: DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=archi, DB_USERNAME=root
- Application uses MySQL database archi. Confirmed.
- Tests use SQLite in-memory via phpunit.xml (isolated).

## 2. SAFETY PRINCIPLES
- Never modify .env, config, migrations, code, tests
- Never run migrate, migrate:fresh, db:seed, or destructive commands
- Never insert/update/delete in archi
- Create separate database archi_manual_test
- Keep archi completely protected

## 3. CREATE SEPARATE DATABASE
**MySQL Workbench:** Create schema archi_manual_test (utf8mb4_unicode_ci). Leave empty. Do not copy data.
**MySQL CLI:** CREATE DATABASE IF NOT EXISTS archi_manual_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

## 4. SAFE CONFIGURATION APPROACH
Do NOT edit .env. Options: use temporary env override for CLI only (and clear), or separate .env file not used by app. Never point running app to new DB without explicit need and ensuring no migrations run against archi.

## 5. SEEDERS (INSPECTION ONLY)
database/seeders/: DatabaseSeeder.php, RolePermissionSeeder.php, SuperAdminSeeder.php, SettingSeeder.php, GradeScaleSeeder.php - appear suitable for test data but DO NOT execute. Only run against archi_manual_test if needed with extreme caution.

## 6. CHECKLIST
- [ ] Verify MySQL running
- [ ] Confirm .env points to archi (unchanged)
- [ ] Create archi_manual_test (empty, separate)
- [ ] Verify isolation
- [ ] Never run migrate/seed against archi

## 7. POTENTIAL ISSUES
| Issue | Risk | Resolution |
|---|---|---|
| Running migrate/seed against default | High | Never run; check DB_DATABASE always |
| App pointing to wrong DB | High | Keep .env unchanged |
| Tests affecting archi | None | Tests isolated by phpunit.xml |

## QUICK VERIFICATION (READ-ONLY)
SHOW DATABASES; USE archi; SHOW TABLES; USE archi_manual_test; SHOW TABLES;

**Guide Created:** No changes made to system. Ready for safe manual testing setup.
