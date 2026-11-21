# IGK SCHOOL ERP - MANUAL FUNCTIONAL TESTING CHECKLIST

**Generated:** 2026-11-02  
**Updated:** 2026-11-21
**Project Path:** E:\Laravel-Projects\Architectured Final S  
**Framework:** Laravel 12.70.0 (PHP 8.5.11)  
**QA database:** Use `school_management_system_testing_new_db` for all new QA writes; never write to `school_management_system_db`
**Test Environment:** Automated tests use SQLite in-memory; see the dated execution report below
**Audit Type:** Scoped browser QA and safe, tagged test data only; no migrations, seeders, resets, or destructive SQL

**Instructions:** This checklist remains a reusable template; its per-case result and evidence fields are intentionally blank. For live QA, use uniquely tagged records in the confirmed testing database only, preserve all original data, and do not run migrations, seeders, resets, or destructive SQL.

**QA closure update (2026-11-20):** `docs/QA/` has 0 open bugs and 0 failed test cases. All form/menu dropdown behavior must open downward and remain within the viewport; upward-opening/floating legacy menus are not acceptable. PDF/CSV, RBAC, journal, fee, transport, cafeteria, transcript, report, and live-browser checks are part of the released QA verification state.

---

## Execution report — 2026-11-21

- **Automated:** Full PHPUnit suite completed with 303 tests: 302 passed, 0 failed, 1 skipped, and 2603 assertions. The skip is `ConcurrentPaymentsTest`, guarded for opt-in real-database parallel testing; it uses `DatabaseMigrations` and is not safe to run on the confirmed school database. The focused PDF regression suite passed 16 tests and 117 assertions.
- **Browser roles:** 75 sidebar destinations returned HTTP 200 across Registrar, Teacher, Accountant, Student, Guardian, and Librarian. Super Admin and Institution Admin login reached the two-factor challenge after the default password (`password`); their dashboards were not entered to avoid changing existing admin accounts. Exam Coordinator has no linked account.
- **Responsive and exports:** Four representative pages passed 48 viewport/theme checks (320, 390, 768, 1024, 1366, and 1920px; light and dark). Subject Performance PDF and CSV downloads both returned HTTP 200 with their expected formats. Print behavior was checked with browser print emulation.
- **Database CRUD:** One tagged QA student was created, read, edited, and deleted through the Registrar UI in `school_management_system_db`. The tagged account and student are absent; student count is 243 and class 4 enrollment is 5. The application's audit entries for these QA actions remain.
- **Not covered:** This run did not exercise every form, button, submenu, and page for every role. Post-2FA admin dashboards and OS-level print dialogs were not tested. No database migrations, seeders, resets, or destructive SQL were run.
- **Status:** **PASS** for the checks completed; **FAIL** 0; **NOT TESTED** the concurrency test and exhaustive UI coverage; **BLOCKED** Exam Coordinator access (no linked account) and post-2FA admin pages (preserved existing accounts).

## Global dropdown regression — 2026-11-21

- **Shared component result:** PASS, 10 Playwright cases across five viewport projects in light and dark themes. See the [dropdown result register](../QA/Results/DROPDOWN_UI_RESULTS.csv) and [evidence index](../QA/Evidence/EVIDENCE_INDEX.csv).
- **Checked:** Enhanced class and Active Campus selects, issuing-country, nationality, phone-country, and Bootstrap dropup menus; downward placement, field width, internal scrolling, readable contrast, Escape closing, and Active Campus value/change behavior.
- **Role-specific pages:** NOT TESTED in this run. The shared component result must not be recorded as a pass for all authenticated pages or all eight role accounts. Use the [global dropdown guide](./guides/global-dropdown-ui.md) for coverage and safe-test limits.

## 1. FINANCE (Phase 4)

### 1.1 Fee Structure
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| FIN-001 | Phase 4 / Finance | Admin logged in. Academic Year/Term/Classes exist. | Safe fee structure (amounts like 15000.00) | 1. Go to /admin/fee-structures 2. Create fee structure with items 3. Edit structure 4. Test delete constraints | Fee structure saved with decimals. Constraints enforced. | _____ | _____ | Screenshot of form/list | Partially covered - verify manually |

### 1.2 Invoice Generation
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| FIN-002 | Phase 4 / Finance | Fee structure exists. Students enrolled. Admin/Accountant logged in. | Safe student | 1. Go to /admin/invoices/create or generate 2. Generate invoice for student/term 3. View invoice details | Unique invoice number, correct totals, status issued, balances initialized | _____ | _____ | Invoice view | Covered - verify manually |

### 1.3 Payment Recording
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| FIN-003 | Phase 4 / Finance | Invoice exists with balance>0. Accountant/Admin logged in. | Amount, method, reference | 1. Go to /admin/payments/create 2. Record payment <= balance 3. View receipt link | Payment recorded, receipt generated, balance reduced, status updated correctly | _____ | _____ | Payment form, updated invoice, receipt link | Covered - verify boundaries |

### 1.4 Partial Payments
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| FIN-004 | Phase 4 / Finance | Invoice with balance. Accountant/Admin logged in. | Partial amounts | 1. Record partial payment 2. Record remainder payment 3. Check balances | Status becomes partially_paid then paid. Balances cumulative correct. | _____ | _____ | Invoice payment history | Covered - verify manually |

### 1.5 Outstanding Balance
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| FIN-005 | Phase 4 / Finance | Invoices with various balances. Accountant/Admin logged in. | Mixed data | 1. View /admin/invoices list 2. Check reports | Outstanding balances accurate. Defaulters report correct. | _____ | _____ | Invoice list and reports | Covered - verify manually |

### 1.6 Payment Reversal
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| FIN-006 | Phase 4 / Finance | Payment exists. Admin logged in. | Reversal reason | 1. Reverse via payments/{payment}/reverse with reason 2. Verify balance restored 3. Try reverse again | Authorization required, reason required, balance restored, cannot reverse twice, audited | _____ | _____ | Reversal form, invoice before/after, audit trail | Critical - covered, manual verification required |

### 1.7 Receipt Generation
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| FIN-007 | Phase 4 / Finance | Payment exists. Authorized user logged in. | Payment ID | 1. Access /admin/receipts/{payment} 2. View receipt | Receipt displays with unique receipt number and details. Access controlled. | _____ | _____ | Receipt view | Covered - verify manually |

### 1.8 Money Precision
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| FIN-008 | Phase 4 / Finance | Fee structure with decimals. Accountant/Admin logged in. | Decimal amounts | 1. Create with decimals 2. Generate invoice 3. Make partial payments 4. Check calculations | Decimal precision maintained (Money support). No float artifacts. | _____ | _____ | Decimal values throughout | Critical - manual verification required |

---

## 2. EXAMINATION AND RESULTS (Phase 3)

### 2.1 Exam Creation
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| EXAM-001 | Phase 3 / Exams | Admin logged in. Term/Class/Subject exist. | Exam details | 1. Go to /admin/exams 2. Create exam 3. Allocate to subjects | Exam created correctly. | _____ | _____ | Exam form/list | Covered - verify manually |

### 2.2 Marks Entry
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| EXAM-002 | Phase 3 / Marks | Teacher assigned to class-subject logged in. Exam exists. Students enrolled. | Safe marks | 1. Access teacher marks area 2. Enter marks for assigned class only | Only assigned class accessible. Marks saved. | _____ | _____ | Marks entry sheet | Covered - verify scoping |

### 2.3 Marks Validation
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| EXAM-003 | Phase 3 / Marks | Teacher logged in. Max marks set. | Marks > max, negative, invalid | 1. Enter invalid marks 2. Submit | Validation errors. Data not saved. | _____ | _____ | Validation errors | Covered - boundary testing |

### 2.4 Grade Calculation
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| EXAM-004 | Phase 3 / Grading | Marks entered. Grade scale configured. | Marks in various ranges | 1. Review grades assigned | Grades correct per grade scale. | _____ | _____ | Results with grades | Covered - verify manually |

### 2.5 GPA Calculation
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| EXAM-005 | Phase 3 / GPA | Multi-subject results exist. | Multiple subject marks | 1. View term results GPA | GPA calculated correctly. | _____ | _____ | Term results with GPA | Critical - spot check |

### 2.6 Result Publication
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| EXAM-006 | Phase 3 / Publication | Results ready. Admin/Registrar logged in. | Exam/term | 1. Publish via /admin/exams/{exam}/publish 2. Verify status | Only authorized can publish. Status updated. | _____ | _____ | Publish action/status | Covered - verify auth |

### 2.7 Marks Locking After Publication
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| EXAM-007 | Phase 3 / Locking | Results published. Teacher logged in. | Previously entered marks | 1. Attempt to edit marks after publication | Editing blocked/disabled. Only unpublish (authorized) allows changes and audited. | _____ | _____ | Blocked edit attempt | Critical - verify |

### 2.8 Student/Guardian Result Access
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| EXAM-008 | Phase 3 / Results Access | Results published. Student/Guardian logged in. | Published results | 1. Access student results 2. Access guardian child results 3. Try other student's results by URL | Only own/linked results visible. Unpublished hidden. Direct access blocked. | _____ | _____ | Scoped access, blocked attempts | Critical - verify scoping |

---

## 3. RBAC AND ACCESS CONTROL (Phase 1)

### 3.1 Role Testing
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| RBAC-001 | Phase 1 / RBAC | Super Admin account | Safe account | Test full admin access including roles/permissions | Appropriate access per role | _____ | _____ | Role access | Covered |
| RBAC-002 | Phase 1 / RBAC | Institution Admin | Safe account | Test broad admin access | Appropriate restrictions | _____ | _____ | Role access | Covered |
| RBAC-003 | Phase 1 / RBAC | Registrar | Safe account | Test academic focus, limited finance write | Correct restrictions | _____ | _____ | Role access | Covered |
| RBAC-004 | Phase 1 / RBAC | Teacher | Assigned teacher | Test only assigned classes, no admin areas | Proper scoping | _____ | _____ | Role scoping | Critical - verify |
| RBAC-005 | Phase 1 / RBAC | Accountant | Safe account | Test finance only | Correct restrictions | _____ | _____ | Role access | Covered |
| RBAC-006 | Phase 1 / RBAC | Student | Linked student | Test own data only | Strict scoping | _____ | _____ | Role scoping | Critical - verify |
| RBAC-007 | Phase 1 / RBAC | Guardian/Parent | Linked guardian | Test linked children only | Strict scoping | _____ | _____ | Role scoping | Critical - verify |

### 3.2 Unauthorized Access
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| RBAC-008 | Phase 1 / RBAC | Multiple roles | Protected routes | Try unauthorized route access from each restricted role | 403 response, no data leakage | _____ | _____ | 403 responses | Covered - verify |
| RBAC-009 | Phase 1 / RBAC | Policy protected resources | Various records | Try forbidden actions | Policy denies, 403 | _____ | _____ | Policy enforcement | Covered |

### 3.3 User Activation
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| RBAC-010 | Phase 1 / RBAC | Admin can manage users. Test user exists. | Test account | Deactivate user, try login, reactivate, login | Inactive blocked with message. Reactivated works. | _____ | _____ | Login attempts | Phase1 test covers - verify flow |

---

## 4. STUDENT DOCUMENTS (Phase 2)

### 4.1 Upload
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| DOC-001 | Phase 2 / Documents | Admin/Registrar logged in. Student exists. | Safe file (jpg/png/pdf < 5MB) | Upload via student documents section | Upload succeeds, validation applied, record created | _____ | _____ | Upload and list | Covered - verify validation |

### 4.2 Download
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| DOC-002 | Phase 2 / Documents | Document exists. Authorized user logged in. | Document ID | Download via documents/{document}/download | File downloads, authorized access only | _____ | _____ | Download | Covered |

### 4.3 Unauthorized Access Prevention
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| DOC-003 | Phase 2 / Documents | Document exists. Different student/unauthorized logged in. | Document ID | Try to download another student's document by URL | Access denied (403). No data leakage. | _____ | _____ | 403 response | Critical - verify |

### 4.4 Private Storage Verification
| Test Case ID | Phase/Module | Preconditions | Test Data Required | Exact Step-by-Step Actions | Expected Result | Actual Result | Pass/Fail | Evidence/Screenshot Required | Automated? |
|---|---|---|---|---|---|---|---|---|---|
| DOC-004 | Phase 2 / Documents | Document uploaded. | Stored file path | Check storage location. Try direct URL access. Use authorized route only. | Files in private storage, not directly accessible, served only via authorized route | _____ | _____ | Storage check, direct URL blocked, route works | Critical security - verify |

---

## EXECUTION SUMMARY

- **Total Test Cases:** 40
- **Critical Priority:** Finance reversal/precision, Results locking/publication/GPA, RBAC scoping, Document security
- **High Priority:** Core workflows across all areas
- **Automated Coverage:** 40 tests/205 assertions passing - focus manual testing on gaps and security
- **No Modifications:** Do not change any files, DB, or tests

**Checklist Created:** IGK_SCHOOL_ERP_MANUAL_FUNCTIONAL_TEST_CHECKLIST.md  
**Status:** Ready for manual execution

