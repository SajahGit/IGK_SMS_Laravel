# ADR-003: Operational artisan commands

Status: accepted. Commands (SOP §12):
- `school:verify-integrity` - read-only invoice/payment consistency check (non-zero exit on problems).
- `school:generate-invoices {class_id} [--term=] [--due-days=30]` - idempotent bulk invoicing.
- `school:close-term [term_id] [--force]` - closes a term; refuses while results are unpublished.
