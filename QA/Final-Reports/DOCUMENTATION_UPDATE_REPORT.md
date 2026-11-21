# Documentation Update Report

## Documentation update — 2026-11-21

The shared dropdown behavior, safe database instructions, and current verification limits were added to the main README, system/architecture guides, admin/accountant/team guides, synthetic QA dataset guide, manual checklist, QA plan, Playwright setup, responsive/theme reports, and Stage 4 summaries. The canonical reference is [Global Dropdown UI](../../docs/guides/global-dropdown-ui.md); browser results and screenshots are linked from that guide.

The application-owned documentation inventory contains 62 Markdown files after adding this guide, excluding generated dependencies and vendored packages. Historical/archive reports and unrelated setup/team guides were left unchanged to preserve their original context; this update covers documents relevant to shared UI, dropdown behavior, or QA status rather than inserting identical notes into every unrelated file.

The update does not claim that every authenticated page or all eight role-specific dropdowns were browser-tested. The shared regression is PASS; role-page coverage remains incomplete as recorded in `QA/Final-Reports/STAGE4_BROWSER_TESTING_REPORT.md`.
