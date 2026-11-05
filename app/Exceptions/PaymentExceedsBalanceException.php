<?php

namespace App\Exceptions;

use App\Support\Money;
use Illuminate\Validation\ValidationException;

/**
 * Thrown when a payment is larger than the invoice's outstanding balance (SOP §12).
 * Extends ValidationException so the UI keeps showing the error under the "amount" field.
 */
class PaymentExceedsBalanceException extends ValidationException
{
    public static function forBalance(string|float|int|null $balance): static
    {
        return static::withMessages([
            'amount' => 'Payment amount exceeds outstanding invoice balance of '.Money::format($balance).'.',
        ]);
    }
}
