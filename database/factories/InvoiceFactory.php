<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * student_id is required: Invoice::factory()->create(['student_id' => $student->id]).
 *
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'term_id' => null,
            'invoice_no' => 'INV-'.date('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(30)->toDateString(),
            'total' => '10000.00',
            'amount_paid' => '0.00',
            'balance' => '10000.00',
            'status' => 'issued',
        ];
    }

    /**
     * Keep factory invoices consistent with what InvoiceService writes: subtotal equals the
     * total (no discount) and one billed line carries the whole amount, so that
     * school:verify-integrity treats them as clean data.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Invoice $invoice) {
            if ($invoice->items()->doesntExist()) {
                $invoice->items()->create(['description' => 'Tuition', 'amount' => $invoice->total]);
            }

            Invoice::whereKey($invoice->id)->update(['subtotal' => $invoice->total]);
            $invoice->refresh();
        });
    }

    public function partiallyPaid(string $paid = '4000.00'): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_paid' => $paid,
            'balance' => bcsub($attributes['total'], $paid, 2),
            'status' => 'partially_paid',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_paid' => $attributes['total'],
            'balance' => '0.00',
            'status' => 'paid',
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'issue_date' => today()->subDays(60)->toDateString(),
            'due_date' => today()->subDays(30)->toDateString(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
