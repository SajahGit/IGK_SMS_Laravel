# Automation Architecture

The testing architecture separates:

- Role fixtures in `tests/e2e/helpers/roles.js`
- Authentication helpers in `tests/e2e/helpers/auth.js`
- Playwright projects for desktop, tablet, and mobile
- PHPUnit unit/feature tests in `tests/Unit` and `tests/Feature`
- Stage 3 planning matrices under `QA/Planning`
- Executed evidence under `QA/Evidence`
- Database safety under `QA/Database-Testing`

Role tests are intentionally isolated and do not execute database mutations unless the approved testing database is active.
