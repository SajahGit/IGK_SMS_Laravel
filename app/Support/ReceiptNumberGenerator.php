<?php

namespace App\Support;

use App\Models\Payment;

/**
 * Generates sequential, per-year receipt numbers: RCP-{year}-{000001}.
 *
 * The number comes from a locked counter row (see NumberSequence), so two cashiers
 * recording payments on different invoices at the same instant can never receive the
 * same number, and a rolled-back payment never burns one (SOP M21 criterion 3).
 * Call it inside the payment's own DB transaction (PaymentService::recordPayment does).
 */
class ReceiptNumberGenerator
{
    public static function next(): string
    {
        $year = date('Y');
        $prefix = "RCP-{$year}-";

        $sequence = NumberSequence::next(
            "receipt:{$year}",
            fn () => NumberSequence::highestSuffix(Payment::class, 'receipt_no', $prefix)
        );

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
