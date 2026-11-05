# Known Issues & Backlog (known-issues.md)

**Project:** IGK School ERP — Laravel 12.70.0  
**SOP Reference:** §29 Handover Artefact #11  
**Owner:** D6  

---

## Open Bugs

| ID | Severity | Module | Description | Workaround | Status |
|---|---|---|---|---|---|
| — | — | — | No Critical or High bugs open | — | — |

## Medium Issues

| ID | Module | Description | Workaround | Est. Effort |
|---|---|---|---|---|
| KI-001 | Coverage | Test coverage not measured (no Xdebug/PCOV available) | Rely on dedicated tests for new logic | 4h (env setup) |
| KI-002 | Manual testing | Full signed end-to-end workflow UAT remains pending; six local role login/dashboard checks and the Super Admin/Institution Admin role boundary were verified in-browser | Automated suite plus local role/queue-worker smoke evidence; team and owner acceptance still required | 8h (workflow UAT) |
| KI-003 | Larastan | Larastan not installed; static analysis not run | Pint runs clean | 2h (install + config) |
| KI-006 | SOP seeding/performance | `DatabaseSeeder` currently creates 11 students and 2 teachers; the SOP's dedicated 240-student `DemoDataSeeder`, 5,000-student `PerfSeeder`, and factories for every model are not implemented | Existing migrations and feature tests use explicit fixtures; do not seed production data for performance checks | Requires isolated seeder/factory implementation and performance validation |

## Low Issues

| ID | Module | Description | Workaround | Est. Effort |
|---|---|---|---|---|
| KI-004 | Curriculum UI | Curriculum model/migration exist; UI completeness partially verified | CurriculumModuleTest covers CRUD + teacher 403 | 4h (full UI verification) |
| KI-005 | Reports acceptance | R1-R11 filters, table columns, and CSV headers were reviewed and aligned; product-owner sign-off and SOP-sized performance checks remain | `ReportsParityTest` covers filter behavior, persisted result data, and R1-R11 CSV/table headers | 3h (owner review/performance check) |

## Post-MVP Backlog (Phase 2 Planning)

| Item | Est. Effort | Notes |
|---|---|---|
| HR and Payroll | 80h | Separate legal/finance domain; requires separate approval |
| Inventory and Stock | 45h | Not in brief |
| Procurement | 45h | Not in brief |
| Multi-Branch Management | 45h + regression | `campus_id` reserved but unused |
| Library Management | 70h | Largest single cut per SOP |
| Transportation Management | 35h | Not in brief |
| LMS | 40h | Not in brief |
| Extended Communication (internal messaging/inbox) | 45h | Announcements exist as replacement |
| Advanced Accounting (GL/COA/Double-entry) | — | Correctly excluded per SOP |
| Transcripts & CGPA across years | 40h | Report card per term only in MVP |
| Online Payment Gateway | 50h + compliance | Manual payments only in MVP |
| Biometric/RFID Attendance | 35h | Manual attendance only |
| SMS Notifications | 18h | Email/in-app only per Q18 |
| Multi-language/RTL | 30h | English only per A7 |

---

## Deployment-Related Known Items

| Item | Status | Notes |
|---|---|---|
| ProductionSeeder | Created | Separate from demo `DatabaseSeeder`; creates no students/invoices/marks |
| deploy.md | Created | v2 runbook with §27 steps, rollback, rehearsal records |
| backup-restore.md | Created | Exact commands + Day-46 drill record template |
| Environment docs | Created | Every `.env` key documented |
| Install guide | Created | Local setup from zero |

## Update (SOP gap-closing pass)

Added without UI changes: `school:verify-integrity`, `school:generate-invoices`, `school:close-term`,
`PaymentExceedsBalanceException`, `tests/Unit/MoneyTest.php`, `deploy/` server files, `pint.json`, `phpstan.neon`,
ADR-001..003, Teacher and Student guides. These were written without a PHP runtime available;
run `php artisan test` and `vendor/bin/pint --test` before relying on them.

## Update (Accounts completion pass #2, 4 Nov 2026)
Written without a PHP runtime again. Before relying on it run: `php artisan migrate`, `php artisan test`, `vendor\bin\pint`, `npm run build`.
Finance regression tests are recorded in `docs/testing/finance-test-case-register.md`; see `IGK_ERP_FINAL_SOP_COMPLIANCE_AUDIT.md` for current acceptance status.
