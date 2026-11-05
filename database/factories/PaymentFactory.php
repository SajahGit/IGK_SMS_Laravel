<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * invoice_id is required. This factory only inserts a row - it does NOT recompute the
 * invoice. To test balances use App\Services\PaymentService::recordPayment().
 *
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'receipt_no' => 'RCP-'.date('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'amount' => '1000.00',
            'method' => fake()->randomElement(['cash', 'bank_transfer']),
            'reference' => null,
            'paid_at' => today()->toDateString(),
        ];
    }

    public function reversed(string $reason = 'Entered in error'): static
    {
        return $this->state(fn () => ['reversed_at' => now(), 'reversal_reason' => $reason]);
    }
}
