# ADR-004: Locked counters for invoice/receipt numbers; reversals as offsetting records

**Status:** accepted (4 Nov 2026) - SOP §11 (payments, invoices), PR-5, TC-FIN-14, TC-FIN-18

## Context
`MAX(number)+1` under `lockForUpdate()` only locks rows that already exist. Two cashiers recording payments on
*different* invoices at the same instant can both read the same maximum. Reversing a payment only flagged the row;
the SOP asks for the original to be retained **and** an offsetting record created.

## Decision
1. One row per series (`invoice:{year}`, `receipt:{year}`) in `number_sequences`, locked `FOR UPDATE`; the number is
   `max(counter, highest existing number) + 1`. The increment runs inside the caller's transaction, so a rolled-back
   payment/invoice rolls the counter back (gapless).
2. A reversal flags the payment (`reversed_at`, `reversal_reason`) and inserts a `payment_reversals` row
   (payment, invoice, amount, reason, reversed_by, reversed_at). Payments are never deleted. Reports keep summing
   `payments WHERE reversed_at IS NULL`, so no figure changes.
3. Invoice lines live in `invoice_items` (negative amount = discount) and are written once at issue time.

## Consequences
Extra tables, additive migration with back-fill. SQLite (tests) cannot prove true parallelism; TC-FIN-14 still needs a
MySQL run.
