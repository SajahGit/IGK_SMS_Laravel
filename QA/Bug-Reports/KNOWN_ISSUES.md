# Known QA Issues

No new Stage 4 bugs are recorded. Historical issues remain in `docs/QA/IGK SMS - QA Testing & Bug Report - Bug Report.csv` and must not be overwritten.

## 2026-11-25 Feature & Regression Verification Notes
- **Mandatory Admin 2FA (`MandatoryAdminTwoFactorTest`):** 5 tests, 55 assertions passed (QR SVG + manual key setup, TOTP on every login, no Remember Device bypass).
- **Administrator Forgot Password OTP (`AdminForgotPasswordOtpTest`):** 7 tests, 79 assertions passed (6-digit Bcrypt-hashed OTP, Gmail SMTP, 5-min expiry, max 5 attempts, 60s cooldown, single-use reset token, 2FA preserved).
- **Teacher & Employee Personal Attendance (`StaffPersonalAttendanceTest`):** 3 tests, 30 assertions passed (`/teacher/profile` & `/my-attendance`).
- **Role-Person Synchronization (`RolePersonSyncTest` & `HrEmployeeEditTest`):** 5 tests, 29 assertions passed (User, Teacher, Employee, Student, and StaffAttendance updates stay synchronized).
- **HR Employees Auto-Sync & Category Filtering (`HrEmployeesCategoryFilterTest` & `HrEmployeeEditTest`):** 4 tests, 36 assertions passed (all teachers and staff auto-synced into `employees` and filterable by category).
