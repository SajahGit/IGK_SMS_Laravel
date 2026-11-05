<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\ReceiptNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFinanceFixtures;
use Tests\TestCase;

/**
 * SOP Q11 / M21 criterion 3: receipt numbers RCP-{YYYY}-{000001} are unique and gapless.
 * (The 50-parallel-request test needs MySQL; SQLite cannot run true parallel writes.)
 */
class ReceiptNumberGeneratorTest extends TestCase
{
    use CreatesFinanceFixtures, RefreshDatabase;

    public function test_first_number_of_the_year_is_000001(): void
    {
        $this->assertSame('RCP-'.date('Y').'-000001', ReceiptNumberGenerator::next());
    }

    public function test_next_number_continues_after_the_highest_existing_number(): void
    {
        $invoice = $this->makeInvoiceFor($this->makeStudentIn($this->makeClass()));
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'receipt_no' => 'RCP-'.date('Y').'-000007',
        ]);

        $this->assertSame('RCP-'.date('Y').'-000008', ReceiptNumberGenerator::next());
    }

    public function test_numbers_from_another_year_do_not_affect_the_sequence(): void
    {
        $invoice = $this->makeInvoiceFor($this->makeStudentIn($this->makeClass()));
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'receipt_no' => 'RCP-2020-000099',
        ]);

        $this->assertSame('RCP-'.date('Y').'-000001', ReceiptNumberGenerator::next());
    }

    public function test_ten_recorded_payments_get_gapless_unique_numbers(): void
    {
        $invoice = $this->makeInvoiceFor($this->makeStudentIn($this->makeClass()), '1000.00');

        for ($i = 0; $i < 10; $i++) {
            PaymentService::recordPayment($invoice->id, '100.00', 'cash', null, today()->toDateString());
        }

        $expected = [];
        for ($i = 1; $i <= 10; $i++) {
            $expected[] = 'RCP-'.date('Y').'-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT);
        }

        $this->assertSame($expected, Payment::orderBy('id')->pluck('receipt_no')->all());
        $this->assertSame(10, Payment::distinct()->count('receipt_no'));
    }
}
