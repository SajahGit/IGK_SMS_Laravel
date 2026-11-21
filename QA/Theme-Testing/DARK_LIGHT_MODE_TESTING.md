# Dark and Light Theme Testing

Status: PARTIAL.

For each of the eight roles, the dashboard was captured in both light and dark modes at 1366 x 768. The theme toggle was used and dark mode persistence after reload was checked on each dashboard.

## Dropdown component addendum — 2026-11-21

The shared dropdown fixture passed 10 Chromium cases across five viewport projects in both themes. Text/background contrast was measured at 4.5:1 or higher for the six shared menu variants, and placement, widths, internal scrolling, and keyboard closing were checked. This result is component-only; contrast and visibility across every authenticated page, modal, chart, table, and form remain NOT TESTED. See the [dropdown results](../Results/DROPDOWN_UI_RESULTS.csv) and [global dropdown guide](../../docs/guides/global-dropdown-ui.md).

The dashboard captures alone do not verify component contrast. The dropdown fixture covers only the listed shared menus; per-page contrast and visibility checks for tables, forms, modals, charts, validation messages, and print/PDF views remain NOT TESTED.

See `QA/Theme-Testing/THEME_RESULTS.csv`.
