# Responsive Testing Report

Status: PARTIAL.

Planned viewports:

- Desktop: 1920 x 1080
- Laptop: 1366 x 768
- Tablet: 768 x 1024
- Mobile: 390 x 844
- Mobile: 360 x 800

All eight role logins and dashboard page loads passed at each of the five viewports (40 checks). The sidebar destinations were opened at 1366 x 768 only. Per-page responsive layout, overflow, modal, table, chart, and form usability have not been verified.

## Dropdown UI addendum — 2026-11-21

The shared dropdown fixture passed at all five planned viewports in light and dark themes (10 cases). Field-bound width, downward opening, long-list scrolling, and text contrast were checked across six shared menu variants. This does not verify each role's actual pages, forms, or module-specific layout; those remain NOT TESTED. See the [dropdown result register](../Results/DROPDOWN_UI_RESULTS.csv) and [global dropdown guide](../../docs/guides/global-dropdown-ui.md).

Detailed login/dashboard results are in `QA/Responsive-Testing/RESPONSIVE_RESULTS.csv`.
