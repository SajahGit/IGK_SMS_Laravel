# Playwright Setup

## Install

```powershell
npm install
npx playwright install
```

## Execute

```powershell
npm run test:e2e
npx playwright test tests/e2e/dropdown-ui.spec.js
npx playwright test tests/e2e/dropdown-ui.spec.js --headed --project=desktop-light-1366
```

## Safety

The Playwright web server is configured for `APP_ENV=testing` and `school_management_system_testing_new_db`. It must not be started against the original database. The database must exist and migrations must be approved before browser execution.

The dropdown UI spec loads the shared production CSS/JavaScript into a database-free browser fixture and does not sign in, submit application forms, or write records. Role login/sidebar specs require authorized QA user/role data in the approved test database; do not run seeders or migrations without separate approval.

## Evidence

Use Playwright trace, screenshot, video, console, and network configuration for executed failures and passed actions. Evidence must be indexed in `QA/Evidence/EVIDENCE_INDEX.csv`.
