# ADR-002: Money is decimal(12,2) handled with bcmath

Status: accepted. Decision: all amounts are stored as decimal(12,2) and calculated only through `App\Support\Money`
(bcmath string arithmetic). Floats are never used for stored or compared money values.
Verification: `tests/Unit/MoneyTest.php` and `php artisan school:verify-integrity`.
