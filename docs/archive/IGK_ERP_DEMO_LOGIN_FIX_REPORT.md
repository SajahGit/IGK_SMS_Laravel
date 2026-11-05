# IGK ERP Demo Login Fix Report

## Root Cause
Original `archi` DB had invalid/missing demo accounts (superadmin hash invalid; registrar/accountant missing). Application code and auth config are correct.

## Active Database
- Default .env: archi (untouched)
- Isolated test DB created: archi_manual_test (migrate:fresh --seed + superadmin hash corrected)

## Verification
All six accounts exist in archi_manual_test with valid password hashes:
superadmin, admin, registrar, teacher, accountant, student (password: password) — all hash_ok=1, active=1.

## Switch to test DB
Set DB_DATABASE=archi_manual_test in .env, run php artisan optimize:clear, restart server. Do not point to archi for testing.

## Automated Tests
79 passed, 360 assertions. Pint passed.

## Browser Verification
Pending manual.

## Remaining Issues
None in test DB. Original archi unchanged.
