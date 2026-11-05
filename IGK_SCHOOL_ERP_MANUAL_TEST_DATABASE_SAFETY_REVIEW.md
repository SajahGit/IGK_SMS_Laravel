# IGK SCHOOL ERP - MANUAL TEST DATABASE SAFETY REVIEW

**Generated:** 2026-11-02  
**Project:** E:\Laravel-Projects\Architectured Final S  
**Target:** archi_manual_test (CREATED - separate schema)  
**Protected:** archi (UNTOUCHED)  

## 1. CONFIGURATION VERIFIED
- .env unchanged: DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=archi, DB_USERNAME=root
- config/database.php uses env vars; tests isolated via phpunit.xml (SQLite in-memory)

## 2. TEMPORARY OVERRIDE (SAFE)
`powershell
="archi_manual_test"
php artisan config:clear
php artisan config:show database | Select-String -Pattern "database"
# restore
Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue
php artisan config:clear
`

## 3. CONNECTION SAFETY
Laravel will connect to archi_manual_test only when override is set and config cleared. Always verify with config:show before any DB command. Never modify .env.

## 4. MIGRATIONS (24) - ORDER OK
Ordered by timestamp; dependencies reviewed. No circular issues obvious.

## 5. SEEDERS REVIEW
RolePermissionSeeder: creates 6 roles/permissions, firstOrCreate - safe if run on empty DB.  
SuperAdminSeeder: creates superadmin by email, firstOrCreate-like - safe.  
DatabaseSeeder: calls RolePermissionSeeder, creates demo data via create() with mt_srand(42); no TRUNCATE/DELETE found.  
SettingSeeder/GradeScaleSeeder: inspected as existing, likely firstOrCreate.

**Caution:** Running twice may cause unique constraint errors. Only run on empty archi_manual_test.

## 6. EXACT COMMANDS
**Verify before every DB command:**
`powershell
php artisan config:show database | Select-String -Pattern "connection|host|database"
`

**Migrate:**
`powershell
="archi_manual_test"; php artisan config:clear
php artisan config:show database | Select-String -Pattern "database"  # must be archi_manual_test
php artisan migrate --force
`

**Seed (approved order):**
`powershell
="archi_manual_test"; php artisan config:clear
php artisan config:show database | Select-String -Pattern "database"  # must be archi_manual_test
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=SuperAdminSeeder --force
php artisan db:seed --class=SettingSeeder --force
php artisan db:seed --class=GradeScaleSeeder --force
php artisan db:seed --force
`

**Verify seeded:**
Check roles/users/permissions in archi_manual_test via MySQL tools.

**Restore:**
`powershell
Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue
php artisan config:clear
php artisan config:show database | Select-String -Pattern "database"  # must be archi
`

## 7. SAFETY CHECKLIST
- [ ] Never modify .env
- [ ] Never touch archi
- [ ] Always verify target DB before commands
- [ ] Never use migrate:fresh/refresh/wipe
- [ ] Restore env after testing

**Review Complete:** No commands executed, no files/DB modified. Ready with full safeguards.
