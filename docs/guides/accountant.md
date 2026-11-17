# Accountant guide

## Sign in and access

Sign in with an active account assigned the **Accountant** role. The app opens `/accountant/dashboard`. The Accountant navigation and routes are separate from administration:

- Controllers: `app/Http/Controllers/Accountant/`
- Views: `resources/views/accountant/`
- Routes: `routes/accountant.php`

The Accountant can manage fee structures, generate and view invoices, record payments, download receipts, and view/export financial reports. In addition, the Accountant has complete multi-branch financial visibility (toggling between "All Campuses" and individual branch contexts) and full access to Accounting & Ledgers (Branch Financial Summary, Chart of Accounts, Journal Entries, Trial Balance, General Ledger, and Income & Expense statements). Accountant users cannot manage users/roles, edit academic records, view audit logs, manage branch definitions, or reverse payments.

## Multi-Branch Financial Visibility

The top navigation bar provides an **Active Campus** dropdown switcher for Accountants:
- **All Campuses**: Default aggregated view displaying invoices, payments, fee structures, and cashflow across all institutional branches simultaneously. Invoices and payment screens display branch badges to identify the originating campus.
- **Specific Campus (e.g. IGK Main Campus, IGK Kandy Campus)**: Narrows the active context so only financial transactions, classes, and students of the selected campus are displayed.
- **Branch Financial Summary (`/admin/accounting/branch-summary`)**: Live overview comparing invoiced, collected, and outstanding totals, inventory footprint, purchase orders, and hostel footprint broken down by branch.

## Accounting & Ledgers Access

Accountants have dedicated access to the core general accounting modules in the sidebar navigation:
- **Branch Financial Summary**: High-level multi-campus financial performance breakdown.
- **Chart of Accounts (`/admin/accounts`)**: View and manage asset, liability, equity, income, and expense accounts.
- **Journal Entries (`/admin/journals`)**: View and create double-entry ledger transactions with multi-branch tagging, plus 1-click auto-posting of unposted invoices and receipts.
- **Trial Balance (`/admin/accounting/trial-balance`)**: Verified balancing of total debits and credits across all accounts.
- **General Ledger (`/admin/accounting/ledger`)**: Detailed line-by-line audit trail of transactions per account.
- **Income & Expense (`/admin/accounting/income-expense`)**: Operating surplus/deficit reports filtered by date range and campus.

## Missing notifications table

The shared application layout checks unread notifications on every authenticated role dashboard. If a dashboard fails with `Table '...notifications' doesn't exist`, verify that the application is connected to the intended local database and inspect the migration state:

```powershell
php artisan migrate:status
php artisan migrate
```

The migration `2026_01_01_000023_complete_sop_academic_relationships` creates Laravel's `notifications` table. Do not use `migrate:fresh` or rerun seeders to address this error; those commands can remove or overwrite existing data. Back up any database that must be retained before applying schema changes.

## Term workflow

1. Confirm the academic year, term, classes, and enrolled students have been set up by the Registrar/Admin.
2. Open **Fee Structures** and create active fee items for the class and term. Enter a positive amount and save.
3. Open **Invoices & Billing** and choose **Generate Class Invoices**.
4. Select the class, term, issue and due dates, and the fee items. Confirm the per-student total and issue invoices.
5. Generation is safe to retry: students already invoiced for the term are skipped instead of being billed twice.
6. Search/filter invoices by invoice number, student, admission number, class, or status. Open an invoice to review its balance and payment history; download the invoice PDF if needed.
7. Choose **Record Payment** from an invoice or the finance menu. Select the invoice from the dropdown (which cleanly formats invoice number, student name, class, branch, and outstanding balance with clean Unicode middle dot `·` separators, avoiding raw HTML entities). Enter the received amount, payment date, method, and optional bank reference.
8. Partial payment is supported. Overpayments, invalid dates, and unsupported methods are rejected. The invoice balance/status updates in the same transaction as the payment. Newly created fee structures and payments immediately appear at the top of their respective lists (`latest('id')`).
9. Print or download the receipt PDF. It contains the receipt number, student/invoice details, amount in figures and words, cashier, and remaining balance.
10. Use **Fee Collection**, **Defaulters**, or **Student Ledger** to review balances and collections. Apply filters and download CSV where needed.

Every finance write is recorded in the audit log. Contact an authorized Admin/Super Admin if a payment needs reversing; Accountant accounts cannot reverse payments.
