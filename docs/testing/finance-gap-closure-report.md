# Accounts module - gap closure (Tue 3 Nov 2026)

Compared the project against `Accounts_Module_Two_Day_Plan_Final.pdf` and `finance-spec.md`.
**Nothing in this pass was executed** - the build environment had no PHP. Run the commands at the bottom first.

## Added
| Owner area | What | Files |
|---|---|---|
| Rajiya (M20) | `InvoiceService::generateForStudent()` (idempotent, full charge for mid-term joiners); bulk now uses it | app/Services/InvoiceService.php |
| Rajiya (M20) | Queued bulk job, `$tries=3`, backoff, result via `GenerateTermInvoices::result($key)` | app/Jobs/GenerateTermInvoices.php |
| Alas (§10.1) | Events InvoiceIssued / PaymentRecorded (after commit), queued idempotent listeners NotifyStudentOfInvoice / SendReceiptEmail, notifications (database + mail) | app/Events, app/Listeners, app/Notifications |
| Hasfa (M21) | Service-level guard: payment amount must be > 0; PaymentRecorded dispatch | app/Services/PaymentService.php |
| Sumaiya (QA) | Factories for FeeStructure, Invoice, Payment (+ HasFactory on the 3 models) | database/factories, app/Models |
| Tests | TC-FIN-01..05, events/notifications, console commands, receipt numbers, end-to-end #2 | tests/Feature/Finance*.php, tests/Unit/ReceiptNumberGeneratorTest.php, tests/Concerns |
| Docs | Test-case register | docs/testing/finance-test-case-register.md |

## Still open (decide with Alas)
1. `invoice_items` table / InvoiceItem model: current schema has one total per invoice (fee structure = one amount, no FeeItem). SOP expects line items. Schema change = shared migration.
2. unique(student_id, term_id) on invoices: not added (term_id nullable; cancelled invoices can be re-issued).
3. Policies FeeStructurePolicy / InvoicePolicy / PaymentPolicy: authorization currently uses permission checks inside controllers.
4. ExportCsv queued job for exports over 2,000 rows; config/school.php constants.
5. DemoDataSeeder finance part and PerfSeeder (5,000 students) - KI-006; performance timings (NFR-1/2) not recorded.
6. TC-FIN-14 parallel-payment test on MySQL; security tests (TC-SEC-01/04/07, sort whitelist, export throttle); audit-row assertions per payment/reversal.
7. Manual UAT checklist in finance-spec.md, Larastan, coverage run.

## Run
```
composer install && npm install        (vendor/ and node_modules/ are not in the zip)
php artisan test --filter=Finance
php artisan test --filter=ReceiptNumberGeneratorTest
php artisan test
vendor\bin\pint --test
```
