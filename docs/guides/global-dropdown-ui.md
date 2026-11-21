# Global Dropdown UI

**Status:** Shared-component regression passed; authenticated role-page coverage remains incomplete.

## Shared implementation

All authenticated role layouts load the shared dropdown behavior from `public/js/app.js` and `public/css/glass.css`; the fix is not tied to one role or module.

- Bootstrap menus are positioned `bottom-start` with absolute positioning and flipping disabled.
- Regular visible `<select>` controls, including Active Campus, are enhanced into searchable menus anchored to their fields. The original select remains the form value/change-event source.
- Issuing-country, nationality, and phone-country menus stay within their component widths and use internal scrolling for long lists.
- Searchable menus match their wrapper width, retain an internal option-list scrollbar, and remain layered above nearby fields/tables.
- Special hidden selects inside country, nationality, and phone components are backing values, not visible native dropdowns.

## Verification completed

`tests/e2e/dropdown-ui.spec.js` loads the production shared CSS/JavaScript with Playwright Chromium in a database-free fixture.

- 10 cases passed: five configured viewports, each in light and dark themes.
- Six menu variants were opened in each case (60 openings total); all opened below their triggers and field-bound menus matched their controls.
- 30 long-list scrolling checks passed. Escape closed each tested menu. Active Campus selection updated the original select and fired its change event.
- Measured foreground/background contrast was at least 4.5:1. No browser console errors, page errors, or failed requests occurred.
- 20 screenshots are indexed in `QA/Evidence/EVIDENCE_INDEX.csv`; results are in `QA/Results/DROPDOWN_UI_RESULTS.csv`.
- Rerun with `npx playwright test tests/e2e/dropdown-ui.spec.js`. For a visible desktop run, add `--headed --project=desktop-light-1366`.

## Coverage limits and safe testing

The fixture validates shared components, not every authenticated page or role-specific module. At the last read-only inspection, `school_management_system_testing_new_db` had no users, roles, permissions, or role profiles. No records, roles, or seed data were written for this regression; no migration, reset, or seeder was run. Actual role-page dropdown verification remains pending safe QA role setup.

Native date/time picker popups are browser/OS UI; their opening direction cannot be controlled by the application dropdown positioning code.

See the [Stage 4 browser report](../../QA/Final-Reports/STAGE4_BROWSER_TESTING_REPORT.md), [dropdown results](../../QA/Results/DROPDOWN_UI_RESULTS.csv), and [evidence index](../../QA/Evidence/EVIDENCE_INDEX.csv).
