<?php

namespace Tests\Feature;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\Concerns\CreatesFinanceFixtures;
use Tests\TestCase;

/**
 * SOP TC-FIN-14: 50 payments recorded at the same moment must give gapless, unique receipt
 * numbers and an exact balance.
 *
 * This needs REAL parallel connections, which an in-memory SQLite test cannot give, so it is
 * skipped unless you point the tests at a real database (MySQL) and opt in:
 *
 *   $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="igk_school_test"; $env:RUN_CONCURRENCY_TEST="1"
 *   php artisan test --filter=ConcurrentPaymentsTest
 *
 * Use an empty throw-away database: the test runs migrate:fresh on it.
 */
class ConcurrentPaymentsTest extends TestCase
{
    use CreatesFinanceFixtures, DatabaseMigrations;

    private const WORKERS = 50;

    protected function setUp(): void
    {
        if (! getenv('RUN_CONCURRENCY_TEST') || in_array(getenv('DB_CONNECTION') ?: 'sqlite', ['sqlite'], true)) {
            $this->markTestSkipped('Set RUN_CONCURRENCY_TEST=1 and a real DB_CONNECTION (e.g. mysql) to run this test.');
        }

        parent::setUp();
    }

    public function test_fifty_parallel_payments_get_unique_gapless_receipts_and_an_exact_balance(): void
    {
        $class = $this->makeClass();
        $student = $this->makeStudentIn($class);
        $fee = FeeStructure::factory()->create(['class_id' => $class->id, 'name' => 'Tuition', 'amount' => '500.00']);
        $invoice = InvoiceService::generateForStudent($student, null, '2026-01-10', '2026-02-10', collect([$fee]));

        $code = sprintf(
            "App\\Services\\PaymentService::recordPayment(%d, '10.00', 'cash', null, '2026-01-11', null);",
            $invoice->id
        );

        $processes = [];
        for ($i = 0; $i < self::WORKERS; $i++) {
            $process = new Process([PHP_BINARY, 'artisan', 'tinker', '--execute='.$code], base_path());
            $process->setTimeout(120);
            $process->start();
            $processes[] = $process;
        }

        foreach ($processes as $process) {
            $process->wait();
        }

        $payments = Payment::where('invoice_id', $invoice->id)->orderBy('id')->get();

        $this->assertCount(self::WORKERS, $payments, 'Every parallel payment must be stored.');
        $this->assertCount(self::WORKERS, $payments->pluck('receipt_no')->unique(), 'Receipt numbers must be unique.');

        $numbers = $payments->map(fn ($p) => (int) substr($p->receipt_no, -6))->sort()->values();
        $this->assertSame(range($numbers->first(), $numbers->first() + self::WORKERS - 1), $numbers->all(), 'Receipt numbers must have no gaps.');

        $invoice = Invoice::find($invoice->id);
        $this->assertSame('0.00', (string) $invoice->balance);
        $this->assertSame('paid', $invoice->status);
    }
}
