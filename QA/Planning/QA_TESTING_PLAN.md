# QA Testing Plan

## Status

Stage 1–3 are complete. Stage 4 remains partial: the shared dropdown regression passed 10 component cases across five viewports and two themes, but authenticated page-by-page verification for all roles has not been completed. No seeder, migration, reset, or database write was run for the dropdown regression.

## Execution environment

- Application: Laravel 12
- Browser automation: Playwright MCP and `@playwright/test`
- Automated unit/feature suite: PHPUnit in-memory SQLite
- Browser QA database: `school_management_system_testing_new_db`
- Original application database: `school_management_system_db`, no QA writes

## Role matrix

Super Admin, Institution Admin, Registrar, Teacher, Accountant, Student, Guardian, and Librarian are separate execution roles.

## Mandatory evidence

Every executable test must record PASS, FAIL, BLOCKED, or NOT TESTED. Evidence files must use Test ID, role, module, device, theme, and status.

## Global dropdown regression — 2026-11-21

The database-free Playwright fixture opened six shared menu variants in each of five viewport projects, in light and dark themes. Downward placement, field width, internal scrolling, contrast, keyboard closing, and Active Campus select/change behavior passed. Results and screenshots are recorded in [DROPDOWN_UI_RESULTS.csv](../Results/DROPDOWN_UI_RESULTS.csv) and [EVIDENCE_INDEX.csv](../Evidence/EVIDENCE_INDEX.csv). This component check does not mark authenticated role pages as tested; see the [global dropdown guide](../../docs/guides/global-dropdown-ui.md).
