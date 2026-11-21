# Database Testing Safety

## Approved testing database

`school_management_system_testing_new_db`

## Original database

`school_management_system_db`

The original database is read-only for this QA process. No migration, seeder, reset, truncation, or CRUD record write may be executed against it.

## Stage gates

1. Database creation must receive separate approval.
2. The active MySQL connection and database name must be verified before migration or browser QA.
3. `php artisan migrate:fresh` and `php artisan db:seed` are not permitted against the original database.
4. Browser/payment CRUD uses disposable QA-prefixed records only.
5. QA records must be deleted or isolated in the testing database after execution.
