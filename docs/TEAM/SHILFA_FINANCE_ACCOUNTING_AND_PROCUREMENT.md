# Team Member: SHILFA
## Domain: Financial Accounting, Billing, Ledgers, Procurement & Stock

**Assigned Engineer:** Shilfa  
**System Role:** Chief Financial Officer / Accountant / Procurement Lead  
**Deliverable Zip File:** `SHILFA_FINANCE_PACKAGE.zip` (located in `G:\ALL DOCS\packages\` and `G:\ALL DOCS\`)  

---

### 1. Functional Scope & Key Responsibilities

Shilfa owns the financial operations of the institution, including student billing, counter cashiering, double-entry bookkeeping, multi-branch finance, and procurement supply chains:
1. **Fee Structures Catalog & Item Rules (`/accountant/fee-structures`):**
   - Program-wide and class-specific fee structures.
   - BCMath high-precision monetary amounts (`Money.php`).
   - Guard against editing fee items that have already been billed to live invoices.
   - Global Auto-Sort by Latest Activity (`updated_at DESC`, `id DESC`) for immediate visibility of recently configured fees.
2. **Cohort Invoicing Engine (`/accountant/invoices`):**
   - Idempotent bulk invoice generation across student cohorts (skipping already billed students).
   - Draft vs Issued status lifecycles, itemized billing, and discounts.
   - Official invoice PDF rendering (`InvoicePdf`) with zero clipping and standard A4 portrait boundaries.
   - Reverse-chronological and latest activity ordering ensuring newly generated invoices appear at the top.
3. **Counter Fee Payments & Receipting Desk (`/accountant/payments/create`):**
   - Real-time cashiering interface with pending balance autofill.
   - Multi-currency payment processing (Cash, Bank Transfer, Cheque).
   - Concurrency lock protection preventing double payments (`DB::transaction` with row locking).
   - Instant generation of official printable receipt PDFs with number-to-words currency transcription.
4. **Online PayHere Payment Gateway Integration (`/student/payhere`):**
   - Secure SHA-256 hash validation for online student/guardian fee checkout.
   - Webhook callback listener updating invoice balances and issuing automated electronic receipts.
5. **Multi-Branch Financial Scoping & Active Campus Switcher:**
   - Topbar campus switcher permitting Accountants to inspect "All Campuses" simultaneously or switch to a specific branch context.
6. **Double-Entry General Ledger System (`/accountant/ledger`):**
   - Strict double-entry accounting invariant ($\sum \text{Debits} = \sum \text{Credits}$).
   - Chart of Accounts, Journal Entries, Trial Balance, Ledger Statements, and Income & Expense Reports.
7. **Procurement & Supply Chain Management (`/procurement`):**
   - Supplier directories, Purchase Orders (PO), and Goods Received Notes (GRN).
   - Global Auto-Sort on Purchase Orders and Suppliers.
   - Campus badges on all invoice listings and payment forms.
8. **Campus-Scoped Payroll & Salary Deduction Transparency (`/accountant/payroll`, `/accountant/salary-deductions`):**
   - Dedicated read-only access for Accountants to inspect staff payroll runs and statutory deductions.
   - Strictly scoped to the accountant's active campus context or all campuses.
   - Payslip transparency view and itemized salary deduction breakdowns.
9. **Global Downward Dropdown Menus & QA Policy:**
   - Action menus and searchable form dropdowns open strictly downward (`bottom-start`) with upward flip disabled, preventing menu cutoff across billing tables and payment modals.
   - 100% QA closure across all financial test execution cases (`docs/QA/`).

---

### 2. Complete File Inventory (73 Files)

#### Routes:
* `routes/accountant.php`
* `routes/accounting.php`
* `routes/procurement.php`

#### Controllers:
* `app/Http/Controllers/Accountant/DashboardController.php`
* `app/Http/Controllers/Accountant/FeeStructureController.php`
* `app/Http/Controllers/Accountant/InvoiceController.php`
* `app/Http/Controllers/Accountant/PaymentController.php`
* `app/Http/Controllers/Accountant/PayrollController.php`
* `app/Http/Controllers/Accountant/SalaryDeductionController.php`
* `app/Http/Controllers/InstitutionAdmin/AccountController.php`
* `app/Http/Controllers/InstitutionAdmin/AccountingReportController.php`
* `app/Http/Controllers/InstitutionAdmin/JournalEntryController.php`
* `app/Http/Controllers/InstitutionAdmin/CashflowEntryController.php`
* `app/Http/Controllers/InstitutionAdmin/InventoryController.php`
* `app/Http/Controllers/Procurement/PurchaseOrderController.php`
* `app/Http/Controllers/Procurement/SupplierController.php`
* `app/Http/Controllers/Student/PayHereController.php`

#### Form Requests:
* `app/Http/Requests/Accountant/FeeStructureRequest.php`
* `app/Http/Requests/Accountant/GenerateInvoicesRequest.php`
* `app/Http/Requests/Accountant/RecordPaymentRequest.php`
* `app/Http/Requests/Accountant/CancelInvoiceRequest.php`
* `app/Http/Requests/Accountant/ReversePaymentRequest.php`
* `app/Http/Requests/Procurement/StorePurchaseOrderRequest.php`
* `app/Http/Requests/Procurement/UpdatePurchaseOrderRequest.php`
* `app/Http/Requests/Procurement/ReceivePurchaseOrderRequest.php`
* `app/Http/Requests/Procurement/StoreSupplierRequest.php`
* `app/Http/Requests/Procurement/UpdateSupplierRequest.php`

#### Domain Services & Support:
* `app/Services/InvoiceService.php`
* `app/Services/PaymentService.php`
* `app/Services/JournalService.php`
* `app/Services/PayHereService.php`
* `app/Services/ProcurementService.php`
* `app/Services/InventoryService.php`
* `app/Support/Money.php`
* `app/Support/NumberToWords.php`
* `app/Support/InvoicePdf.php`

#### Models:
* `app/Models/FeeStructure.php`
* `app/Models/FeeItem.php`
* `app/Models/Invoice.php`
* `app/Models/InvoiceItem.php`
* `app/Models/Payment.php`
* `app/Models/PaymentReversal.php`
* `app/Models/PayHereTransaction.php`
* `app/Models/Account.php`
* `app/Models/JournalEntry.php`
* `app/Models/JournalLine.php`
* `app/Models/Supplier.php`
* `app/Models/PurchaseOrder.php`
* `app/Models/PurchaseOrderItem.php`
* `app/Models/InventoryItem.php`
* `app/Models/InventoryMovement.php`
* `app/Models/SavedFilter.php`

#### Blade Views:
* `resources/views/Accountant/` (AccountantDashboard, AccountantFeeStructuresIndex, AccountantInvoicesIndex, AccountantInvoicesCreate, AccountantInvoicesShow, AccountantPaymentsCreate, AccountantReceiptShow, PayrollIndex, PayrollShow, SalaryDeductionsIndex)
* `resources/views/Accounting/` (AccountsIndex, AccountsForm, JournalsIndex, JournalsForm, JournalsShow, CashflowForm, TrialBalance, Ledger, IncomeExpense)
* `resources/views/Procurement/` (PurchaseOrdersIndex, PurchaseOrderForm, PurchaseOrderShow, SuppliersIndex, SupplierForm)
* `resources/views/InstitutionAdmin/InventoryIndex.blade.php`
* `resources/views/InstitutionAdmin/InventoryForm.blade.php`
* `resources/views/InstitutionAdmin/InventoryShow.blade.php`
* `resources/views/Pdf/PdfInvoice.blade.php`
* `resources/views/Pdf/PdfReceipt.blade.php`

#### Feature Test Suites:
* `tests/Feature/AccountantAllBranchFinanceTest.php`
* `tests/Feature/AccountantFinanceAccessTest.php`
* `tests/Feature/FinanceEndToEndWorkflowTest.php`
* `tests/Feature/AccountsModuleCompletionTest.php`
* `tests/Feature/ConcurrentPaymentsTest.php`
* `tests/Feature/AccountingReportTest.php`
* `tests/Feature/FeeItemsAndDraftInvoicesTest.php`
* `tests/Feature/FeeStructureImmutabilityTest.php`
* `tests/Feature/FinanceInvoiceWorkflowTest.php`
* `tests/Feature/Phase4FinanceModuleTest.php`

---

### 3. Step-by-Step Verification Commands for Shilfa

```powershell
# Run all accountant, billing, ledger, and multi-campus finance tests
php vendor/bin/phpunit tests/Feature/AccountantAllBranchFinanceTest.php tests/Feature/AccountantFinanceAccessTest.php tests/Feature/FinanceEndToEndWorkflowTest.php tests/Feature/AccountsModuleCompletionTest.php tests/Feature/ConcurrentPaymentsTest.php
```
