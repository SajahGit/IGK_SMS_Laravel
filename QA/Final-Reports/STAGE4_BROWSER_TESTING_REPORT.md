# Stage 4 Browser Testing Report

**Status:** PARTIAL / NOT COMPLETE

## Executed

- Eight roles successfully authenticated and reached their dashboards in the browser.
- Super Admin and Institution Admin 2FA was completed using the application's local demo-code flow.
- 283 visible sidebar destinations returned HTTP 200 at 1366x768 and were recorded in `QA/Results/STAGE4_SIDEBAR_RESULTS.csv`.
- 40 role/login combinations passed across the configured desktop, tablet, and mobile viewports.
- Two dashboard theme screenshots were captured for each role at 1366x768.
- The Playwright Chromium runtime was installed and used.
- Browser smoke tests reused the already-running application at port 8000, whose Laravel configuration selected `school_management_system_db`. No business-record CRUD, migration, or seeder was run. Normal login may write session state through the application's configured session driver.

## Execution counts

- Total executed read-only tests: 323
- PASS: 323
- FAIL: 0
- BLOCKED: 0
- Browser console errors recorded: 0
- Network failures recorded: 2 occurrences of a Chart.js CDN request aborted during page navigation; the affected dashboard still returned HTTP 200.

## Not yet executed

The following mandatory Stage 4 scopes remain incomplete:

- Every button and form across every accessible page.
- Create/edit/update/delete CRUD workflows.
- File uploads/downloads and report exports.
- Full dark/light contrast verification for every page.
- Theme-switch and persistence behavior across every role and page.
- Sidebar crawling at every configured viewport.
- Unauthorized direct-URL and backend-request verification for every restricted action.
- Browser console/network issue classification across every page.
- QA-only MySQL CRUD records and cleanup.

## Database and future runs

The original database was used only because the existing server was already running there, in line with the user's explicit direction. No application CRUD operation, migration, or seeder was performed. The Playwright configuration is now isolated on port 8011 and pinned to `school_management_system_testing_new_db` for future runs.

## Global dropdown regression — 2026-11-21

**Status:** Shared-component regression PASS; role-page verification remains BLOCKED.

### Root cause and fix

- The shared dropdown initializer forced Bootstrap Popper to use `fixed` positioning. Searchable menu CSS used `width: 100%`, which made the menu viewport-wide instead of field-wide.
- Some country menus also had a fixed minimum width, causing them to overflow narrow mobile controls.
- Bootstrap menus now use a downward `bottom-start` placement without flip, and searchable controls use static positioning anchored to their wrapper. Country, nationality, phone-country, and searchable menus are constrained to their component widths and retain internal scrolling.
- The branch selector now uses the same searchable field-aligned menu as other regular selects. Existing form submission on branch changes remains attached to the original select.

### Browser results

- Ran the shared application JavaScript and CSS with Playwright Chromium against a database-free fixture.
- 10 cases passed: five configured viewports, each tested in light and dark themes (60 menu openings total).
- Each case opened six shared menu variants: enhanced class select, active-branch select, issuing-country, nationality, phone-country, and a Bootstrap dropup. All opened below their triggers; field-bound menus matched their controls.
- Long searchable, country, and phone-country lists passed 30 internal-scroll checks. Keyboard Escape closed menus. The active-campus option updated the original select and fired its change event. Text/background contrast was at least 4.5:1. No browser console errors, page errors, or failed requests were recorded.
- The final desktop 1366x768 light/dark checks were also rerun in headed Chromium: 2 passed.
- 20 screenshots were captured (searchable and standard menus per viewport/theme) and indexed in `QA/Evidence/EVIDENCE_INDEX.csv`. A prior live Teacher Attendance screenshot is also indexed.
- Results are recorded in `QA/Results/DROPDOWN_UI_RESULTS.csv`.

### Coverage limitation

This verifies the shared components that are used across roles; it does **not** prove every authenticated page or role-specific dropdown has been opened in the application. Read-only inspection confirmed that the approved test database currently has 0 users, roles, permissions, or role profiles. QA-account creation was subsequently approved, but role/permission setup still requires separate seeder approval; no users or seeders have been added. The original database was not accessed for this dropdown regression.

Native browser date/time pickers remain browser/OS UI, not application dropdown menus; their opening direction is controlled by the browser and operating system.
