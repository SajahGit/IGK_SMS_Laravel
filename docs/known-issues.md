# Known Issues & Backlog (known-issues.md)

**Project:** IGK International School ERP — Laravel 12.71.0
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
| KI-002 | Manual testing | Full signed end-to-end workflow UAT remains pending; the shared browser is at login and no authenticated role-dashboard walkthrough has been performed | Automated suite; team and owner acceptance still required. Do not bypass 2FA | 8h (workflow UAT) |
| KI-003 | Larastan | Larastan not installed; static analysis not run | Pint runs clean | 2h (install + config) |
| KI-006 | SOP seeding/performance | `PerfSeeder` creates exactly 5,000 international-school student records and is repeatability-tested; the `PerfSeeder` → `FullSampleDataSeeder` sequence now passes under the test process's 128 MiB memory limit. Response-time/concurrency thresholds and factories for every model remain unverified | Do not claim NFR-1 performance compliance from record-count/seeding tests | Measure SOP response-time and concurrency thresholds |
| KI-007 | Full-suite regressions | Latest `php artisan test` run: 9 failures remain across accountant total formatting, 2FA login redirect expectations, registrar authorization, and report CSV/stream responses | The changed international-seeding, input-validation, role, and UI-filter paths pass their focused checks; resolve these failures before claiming full-suite acceptance | Investigate by failing test and align behavior with the SOP |

## Low Issues

| ID | Module | Description | Workaround | Est. Effort |
|---|---|---|---|---|
| KI-004 | Curriculum UI | Curriculum model/migration exist; UI completeness partially verified | CurriculumModuleTest covers CRUD + teacher 403 | 4h (full UI verification) |
| KI-005 | Reports acceptance | R1-R11 filters, table columns, and CSV headers were reviewed and aligned; product-owner sign-off and SOP-sized performance checks remain | `ReportsParityTest` covers filter behavior, persisted result data, and R1-R11 CSV/table headers | 3h (owner review/performance check) |

## Expanded SOP scope gaps

The user requests that SHOULD-HAVE and POST-MVP work be treated as mandatory even where the HTML SOP explicitly excludes it from MVP. Do not silently rewrite the HTML authority or claim these gaps are complete.

| Item | Current status |
|---|---|
| Dashboard charts | Not implemented; dashboards currently show role-based indicators. |
| Saved filters | No saved-filter entity or workflow found. |
| Multi-year transcript/CGPA sample | Transcript and CGPA views/services exist, but the demo seed currently contains one academic year. |
| SMS history/workflow | Outbound gateway abstraction exists; no persisted SMS workflow/history or safe sample records. |
| Multilingual/RTL | English LTR only. |
| Legacy migration | Validated CSV import exists, but no legacy mapping, reconciliation or rehearsed migration. |
| Mobile app | No native app; responsive web is not equivalent. |
| Authenticated UAT and security enrollment | Full role-by-role screen acceptance remains pending; 2FA secrets must be enrolled by each user and are deliberately not demo-seeded. |
| Performance NFR | Exact 5,000-student dataset is tested; response-time/concurrency thresholds are not measured. |

---

## Deployment-Related Known Items

| Item | Status | Notes |
|---|---|---|
| ProductionSeeder | Created | Separate from demo `DatabaseSeeder`; creates no students/invoices/marks |
| deploy.md | Created | v2 runbook with §27 steps, rollback, rehearsal records |
| backup-restore.md | Created | Exact commands + Day-46 drill record template |
| Environment docs | Created | Every `.env` key documented |
| Install guide | Created | Local setup from zero |

## Verification record

The international demo-seeder and 5,000-student performance tests pass, including repeatability and international-level/track consistency. The full-module seeder also passes after the performance dataset in the normal test memory limit. Full sample data includes pending PayHere, matched/unmatched biometric events and a deliberately expired API token without external calls or usable token secrets. Phone/document validation, CSV import and field-level feedback tests pass. The latest full suite still has nine failures (KI-007); authenticated role-dashboard UAT remains pending because the available shared browser page is at login and 2FA must not be bypassed.

Finance regression tests are recorded in `docs/testing/finance-test-case-register.md`; the HTML SOP is the requirements authority.
