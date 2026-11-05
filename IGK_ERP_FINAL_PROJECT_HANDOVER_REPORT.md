# IGK ERP Project Handover Status

> This handover is not a declaration of full SOP acceptance. See [IGK_ERP_FINAL_SOP_COMPLIANCE_AUDIT.md](./IGK_ERP_FINAL_SOP_COMPLIANCE_AUDIT.md) for current verification evidence and remaining sign-off work.

## Completed
- The previously identified implementation gaps have code paths for scoped Registrar academic access, teacher department/status, multiple guardians and primary contact, term-aware class-subject offerings, session-based attendance, targeted announcements/notifications, and R1-R11 reporting.
- Tests: 201 passed, 965 assertions, 1 skipped after removing the non-SOP cashbook application feature. Pint passed for the modified route files.
- Local staging checks: fresh migration/seed on disposable SQLite, six-role dashboard walkthrough, Super Admin-only role management, Institution Admin 403, and a real `queue:work database --once` term-results job.
- R1-R11 controller filters, visible columns, and CSV headers were reviewed; mismatched export headers were aligned and regression-tested.
- Handover documents include [README-SETUP.md](./README-SETUP.md), README-SETUP.md, SOP traceability, module compliance, architecture, and role-specific guides.
- No production database migration or seed was run.

## Demo Credentials

See [README-SETUP.md](./README-SETUP.md) for the full local-only account list and passwords. Do not use demo credentials in production.

## Remaining Acceptance and Sign-off

- Complete signed end-to-end role/workflow UAT; the local walkthrough only validated sign-in, dashboards, and the Super Admin/Institution Admin role boundary.
- Obtain product-owner sign-off for Appendix A reports and complete SOP-sized report performance checks.
- Measure required test coverage and add tests for any uncovered MUST-HAVE acceptance criteria.
- Reconcile the SOP's 78-permission count with the seeder's 110 unique permissions using an approved catalog.
- Complete security review, performance checks, and approved production backup/deployment/restore before production approval.

## Explicitly Post-MVP
Library, multi-campus, transcripts/CGPA, online payment gateway, internal messaging, HR/payroll, hostel/transport/inventory, public API, elective registration, biometric, SMS, multi-language, legacy migration, and mobile app.

## Manual verification still required
Full workflow UAT, mobile responsiveness, PDF report layout, complete manual regression, signed UAT, security/performance review, and approved production backup/deployment/restore. Local role-dashboard and queue-worker checks are recorded in the compliance audit.

## Database safety
Tests use isolated SQLite. The only `migrate:fresh` rehearsal targeted a newly created disposable SQLite file. Do not migrate or seed the configured shared/production database.
