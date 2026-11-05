# Accountant guide

## Sign in and access

Sign in with an active account assigned the **Accountant** role. The app opens `/accountant/dashboard`. The Accountant navigation and routes are separate from administration:

- Controllers: `app/Http/Controllers/Accountant/`
- Views: `resources/views/accountant/`
- Routes: `routes/accountant.php`

The Accountant can manage fee structures, generate and view invoices, record payments, download receipts, and view/export financial reports. Accountant users cannot manage users/roles, edit academic records, view audit logs, or reverse payments.

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
7. Choose **Record Payment** from an invoice or the finance menu. Select the invoice, enter the received amount, payment date, method, and optional bank reference.
8. Partial payment is supported. Overpayments, invalid dates, and unsupported methods are rejected. The invoice balance/status updates in the same transaction as the payment.
9. Print or download the receipt PDF. It contains the receipt number, student/invoice details, amount in figures and words, cashier, and remaining balance.
10. Use **Fee Collection**, **Defaulters**, or **Student Ledger** to review balances and collections. Apply filters and download CSV where needed.

Every finance write is recorded in the audit log. Contact an authorized Admin/Super Admin if a payment needs reversing; Accountant accounts cannot reverse payments.
