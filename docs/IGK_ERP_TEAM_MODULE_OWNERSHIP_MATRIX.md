# IGK ERP Team Module Ownership Matrix

இந்தப் பங்கு ஒதுக்கீடு team planning-க்கானது. **Core / Accounts** என்பது MVP வேலைப் பிரிவு; **Post-MVP + Missing Modules** என்பது அடுத்த கட்ட விரிவாக்கம். ஒரு feature ஏற்கனவே ஓரளவு உள்ளது என்பதற்காக அதை மீண்டும் எழுத வேண்டாம்—முதலில் தற்போதைய routes, controllers, models, migrations, seeders, tests-ஐப் பார்த்து gap மட்டும் implement செய்யவும்.

## Core

| Member | Assigned modules |
|---|---|
| Alas | Admin; Parent/Guardian |
| Rajiya | Student |
| Hasfa | Reports |
| Sumaiya | Teacher |

## Accounts

| Member | Assigned modules |
|---|---|
| Shilfa | Fee structures; fee items |
| Rajiya | Payments; receipts |
| Alas | Invoices; shared files |
| Sumaiya | Term automation; artisan commands; events |
| Hasfa | Dashboard; portals; PDF; access tests |

## Post-MVP + Missing Modules

| Member | Assigned modules / enhancements |
|---|---|
| Alas | HR and payroll; staff attendance; CSV bulk imports; saved filter presets; two-factor authentication |
| Shilfa | Finance and accounting ledger; online payment gateway; multi-branch |
| Rajiya | Inventory and stock; procurement; transportation |
| Sumaiya | Messaging and SMS; transcripts and CGPA; daily absentee digest |
| Hasfa | Library; LMS; dashboard charts; PDF export of reports |

## Working boundaries

- Each owner leads implementation, tests, and documentation for the assigned module. Coordinate cross-module dependencies before changing shared code.
- Alas coordinates shared authentication/authorization foundations and requests affecting shared routes, policies, permissions, configuration, common models/services, migrations, or layouts. Agree the owner before editing a shared file; do not have two people modify the same file concurrently.
- Use a separate feature branch per task and submit a focused Pull Request for review; do not push module work directly to `main`.
- A module assignment describes ownership, not a claim that the feature is missing or complete. Reuse existing implementation and add only the agreed gap/enhancement.

## Current project code map

Paths below are starting points, not an exhaustive list; follow existing code and route conventions:

| Area | Existing code locations |
|---|---|
| Admin / authentication | `app/Http/Controllers/InstitutionAdmin/`, `app/Http/Controllers/SuperAdmin/`, `app/Http/Controllers/Auth/` |
| Student / guardian | `app/Http/Controllers/Student/`, `app/Http/Controllers/Guardian/`, `app/Models/` |
| Teacher / academic | `app/Http/Controllers/Teacher/`, `app/Http/Controllers/Registrar/`, `app/Models/` |
| Accounts / finance | `app/Http/Controllers/Accountant/`, `app/Http/Controllers/Accounting/` |
| Reports | `app/Http/Controllers/Report/` |
| Shared and integration work | `app/Policies/`, `app/Services/`, `app/Support/`, `app/Observers/`, `routes/`, `database/migrations/`, `database/seeders/`, `tests/` |
| Views | Existing feature folders under `resources/views/` |

## Setup and shared demo data

Every developer should run the same committed migrations and seeders against their **own** local database. Follow [README-SETUP.md](./README-SETUP.md) to create `.env`, run `php artisan migrate --seed`, start the app, and safely refresh local demo data. Demo credentials and role-wise test examples are in [sample dataset.md](./sample%20dataset.md). Do not share a database file or `.env`.
