# Final QA Report

Status: PARTIAL / NOT COMPLETE.

The shared dropdown component regression has passed 10 Chromium cases across five viewport projects in both themes. Authenticated page-by-page testing for all eight roles and the remaining Stage 4/Stage 5 scope are not complete. This report must not be interpreted as a full application sign-off.

## 2026-11-25 Backend Feature Test Update
Targeted PHPUnit feature suites verified the following modules on 2026-11-25:
- `MandatoryAdminTwoFactorTest`: 5 tests, 55 assertions (PASS)
- `AdminForgotPasswordOtpTest`: 7 tests, 79 assertions (PASS)
- `StaffPersonalAttendanceTest`: 3 tests, 30 assertions (PASS)
- `RolePersonSyncTest` + `HrEmployeeEditTest`: 5 tests, 29 assertions (PASS)
- `HrEmployeesCategoryFilterTest` + `HrEmployeeEditTest`: 4 tests, 36 assertions (PASS)
- `Phase1RbacAndModuleTest`: 20 tests, 108 assertions (PASS)
- `InternationalMultiBranchAndPayrollTest`: passed within combined 22-test run (216 assertions, PASS)


---

## 2026-11-28 Verification Addendum (Learning Materials, Notifications & Finance Sync)
- **Automated Verification (`php vendor/bin/phpunit`):**
  - `LearningMaterialsModuleTest`, `NotificationViewTest`, `RoleLoginVerificationTest`, `FinanceEndToEndWorkflowTest`, `FinanceInvoiceWorkflowTest`, `Phase4FinanceModuleTest`, `AccountantFinanceAccessTest`, `AccountingReportTest`, `ReportsParityTest`: **68 tests, 625 assertions passed**.
  - Earlier 8-suite finance baseline (`ConcurrentPaymentsTest` included): **52 tests, 317 assertions, 1 skipped** (SQLite concurrency lock test).
