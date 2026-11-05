# Finance test-case register (SOP §18.4, handover artefact 10)

Scope: M19 fee structures, M20 invoices, M21 payments/receipts, M24 finance reports R9-R11.
Latest full run: `php artisan test` — 201 passed, 965 assertions, 1 skipped.
Status legend: **PASS** = covered by a passing automated test. **Manual** = needs a person in the browser. **Gap** = no automated coverage yet.

| ID | Scenario | Expected result | Priority | Owner | Type | Test / status |
|---|---|---|---|---|---|---|
| TC-FIN-01 | Bulk generation: one invoice per student; total = sum of active fee items; balance = total | 1 invoice per student, status issued | P1 | Rajiya | Auto | FinanceInvoiceWorkflowTest::tc_fin_01 - PASS |
| TC-FIN-02 | Re-run bulk generation | 0 created, "N skipped (already invoiced)" | P1 | Rajiya | Auto | FinanceInvoiceWorkflowTest::tc_fin_02 (service + job) - PASS |
| TC-FIN-03 | Invoice numbers sequential and unique | INV-YYYY-0001, 0002, ... no gaps/reuse | P1 | Rajiya | Auto | FinanceInvoiceWorkflowTest::tc_fin_03 - PASS |
| TC-FIN-04 | No active fee structure selected | Rejected, nothing written | P1 | Rajiya | Auto | FinanceInvoiceWorkflowTest::tc_fin_04 - PASS |
| TC-FIN-05 | Failure mid-batch | Rollback, no half batch | P1 | Rajiya | Auto | FinanceInvoiceWorkflowTest::tc_fin_05 - PASS |
| TC-FIN-06..09 | Invoice list search/filters/pagination, show page, PDF amounts equal screen | Filters kept in URL; PDF = screen to the cent | P2 | Rajiya | Auto + Manual | AccountantFinanceAccessTest, PdfDocumentsTest - Existing; filter/pagination assertions - **Gap** |
| TC-FIN-10 | Payment of exactly the balance | status paid, balance 0.00 | P1 | Hasfa | Auto | Phase4FinanceModuleTest - Existing |
| TC-FIN-11 | Receipt numbers unique, gapless, sequential | RCP-YYYY-000001.. | P1 | Hasfa | Auto | ReceiptNumberGeneratorTest - PASS |
| TC-FIN-12 | Overpayment (500.00 balance, pay 600.00) | Rejected, no payment row, balance unchanged | P1 | Hasfa | Auto | Phase4FinanceModuleTest - Existing |
| TC-FIN-12b | Zero / negative payment | Rejected, no rows written | P1 | Hasfa | Auto | FinanceEventsAndNotificationsTest - PASS |
| TC-FIN-13 | Audit row per payment | activity row `payment_received` | P1 | Sumaiya | Auto | AccountsModuleCompletionTest - PASS |
| TC-FIN-14 | Two cashiers pay the full balance at once; 50 parallel requests | One succeeds; balance 0.00; no duplicate receipt numbers | P1 | Hasfa | Auto (MySQL only) | **Gap** - needs a parallel test on MySQL; SQLite cannot run true parallel writes |
| TC-FIN-15..20 | Reversal: Super Admin only, mandatory reason, offsetting record, balance restored, payment never deleted | See SOP TC-FIN-18 | P1 | Hasfa + Alas | Auto | AccountsModuleCompletionTest - PASS |
| TC-EVT-01 | Events dispatched for invoice and payment; listeners idempotent; failing listener never rolls back the payment | | P1 | Alas | Auto | FinanceEventsAndNotificationsTest - PASS |
| TC-CMD-01 | verify-integrity passes on clean data, fails when balance/amount_paid altered | | P1 | Alas | Auto | FinanceConsoleCommandsTest - PASS |
| TC-CMD-02 | generate-invoices creates N then 0; close-term refuses while results unpublished | | P1 | Alas | Auto | FinanceConsoleCommandsTest - PASS |
| E2E-2 | Fee structure > invoice > payment > receipt > balance report agree | Figures agree; verify-integrity passes | P1 | Alas + Sumaiya | Auto | FinanceEndToEndWorkflowTest - PASS |
| TC-RPT-02 | Teacher gets 403 on financial report and its csv | 403 / 403 | P1 | Sumaiya | Auto | AccountsModuleCompletionTest - PASS |
| TC-RPT-04..06 | R10 defaulters: filters, CSV equals screen, empty state | | P2 | Rajiya | Auto | ReportsParityTest - Existing (headers/filters) |
| TC-SEC-01/04/07 | Mass assignment, stored XSS in fee names, CSRF | | P1 | Sumaiya | Auto | **Gap** |


## Accounts completion pass (Wed 4 Nov 2026) - `tests/Feature/AccountsModuleCompletionTest.php`
All rows below passed in the full `php artisan test` run.

| ID | Scenario | Test |
|---|---|---|
| TC-FIN-01b | One invoice line per fee + a negative Discount line; subtotal/discount/total add up | test_invoice_gets_one_line_per_fee_and_a_negative_discount_line |
| TC-FIN-01c | Discount larger than the amount billed is rejected, nothing written | test_discount_larger_than_the_amount_billed_is_rejected_and_nothing_is_written |
| TC-FIN-01d | Discount entered on the bulk form reaches every invoice | test_bulk_generation_through_the_form_applies_the_discount |
| TC-FIN-06 | Applicant / withdrawn / graduated students are not billed | test_withdrawn_graduated_and_applicant_students_are_not_billed |
| TC-FIN-07 | A fee structure for another term is not billed | test_a_fee_structure_for_another_term_is_not_applied |
| TC-FIN-11b | Receipt numbers for different invoices share one locked counter | test_receipt_numbers_across_different_invoices_come_from_one_counter |
| TC-FIN-11c | A rolled-back payment never burns a receipt number (gapless) | test_a_rolled_back_payment_does_not_burn_a_receipt_number |
| TC-FIN-12c | Amount with 3 decimals rejected (service + form) | test_payment_with_more_than_two_decimals_is_rejected_by_service_and_form |
| TC-FIN-13 | Audit row per payment and per reversal | test_every_payment_and_reversal_writes_an_audit_row |
| TC-FIN-18 | Reversal keeps the payment, writes the offsetting record, restores balance, once only | test_reversal_keeps_the_payment_and_writes_an_offsetting_record |
| TC-FIN-18b | Reversal reason mandatory | test_reversal_requires_a_reason |
| TC-FIN-18c | Only Super Admin may reverse (Institution Admin 403) | test_only_a_super_admin_can_reverse_a_payment_over_http |
| BUG-FIN-01 | Accountant opens an invoice that has a payment (used to throw RouteNotFound) | test_accountant_can_open_an_invoice_that_already_has_a_payment |
| TC-FIN-19 | Super Admin sees the Reverse button | test_super_admin_sees_the_reverse_button_on_the_admin_invoice_page |
| TC-FIN-20 | Cancel unpaid invoice with reason; no payment afterwards | test_unpaid_invoice_can_be_cancelled_with_a_reason_and_then_takes_no_payments |
| TC-FIN-20b | Cancel needs a reason and is refused while payments exist | test_cancel_needs_a_reason_and_is_refused_while_payments_exist |
| TC-FIN-21 | Duplicate fee name for the same class + term rejected | test_duplicate_fee_name_for_the_same_class_and_term_is_rejected |
| TC-FIN-21b | Fee amount with 3 decimals rejected | test_fee_amount_with_three_decimals_is_rejected |
| TC-FIN-22 | Dashboard totals ignore reversed payments and cancelled invoices | test_dashboard_totals_ignore_reversed_payments_and_cancelled_invoices |
| TC-CMD-01b | verify-integrity flags tampered invoice lines | test_verify_integrity_flags_tampered_invoice_lines |
| TC-RPT-02 | Teacher 403 on financial reports and their CSV routes | test_teacher_gets_403_on_the_financial_report_and_its_csv |
| PR-2 | Student gets own invoice PDF, 404 for someone else's | test_student_can_download_own_invoice_pdf_but_not_someone_elses |

Still **Gap** (needs MySQL or a person): TC-FIN-14 true parallel payments (50 requests), SOP-sized performance timings, signed manual UAT.
