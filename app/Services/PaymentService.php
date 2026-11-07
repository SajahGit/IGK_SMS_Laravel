<?php

namespace App\Services;

use App\Events\PaymentRecorded;
use App\Exceptions\PaymentExceedsBalanceException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Support\AuditLogger;
use App\Support\Money;
use App\Support\ReceiptNumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentService
{
    /**
     * @deprecated Use App\Support\ReceiptNumberGenerator::next() directly.
     *             Kept so any existing callers referencing this method keep working.
     */
    public static function nextReceiptNumber(): string
    {
        return ReceiptNumberGenerator::next();
    }

    public static function recordPayment(
        int $invoiceId,
        string|float $amount,
        string $method,
        ?string $reference,
        string $paidAt,
        ?int $receivedBy = null
    ): Payment {
        if (! is_numeric($amount) || (float) $amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        if (! Money::hasValidScale($amount)) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be a positive figure with at most two decimal places.',
            ]);
        }

        $amount = Money::add($amount, '0.00');

        if (Money::comp($amount, '0.00') <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($invoiceId, $amount, $method, $reference, $paidAt, $receivedBy) {
            /** @var Invoice $invoice */
            $invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);

            if ($invoice->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'amount' => 'Cannot record payment for a cancelled invoice.',
                ]);
            }

            if ($invoice->status === 'draft') {
                throw ValidationException::withMessages([
                    'amount' => 'This invoice is still a draft. Issue it before recording payments.',
                ]);
            }

            if (Money::comp($invoice->balance, '0.00') <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This invoice is already fully paid.',
                ]);
            }

            // Reject overpayment
            if (Money::comp($amount, $invoice->balance) === 1) {
                throw PaymentExceedsBalanceException::forBalance($invoice->balance);
            }

            $receiptNo = ReceiptNumberGenerator::next();

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'receipt_no' => $receiptNo,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'paid_at' => $paidAt,
                'received_by' => $receivedBy ?? auth()->id(),
            ]);

            $newAmountPaid = Money::add($invoice->amount_paid, $amount);
            $newBalance = Money::sub($invoice->total, $newAmountPaid);
            $newStatus = Money::comp($newBalance, '0.00') <= 0 ? 'paid' : 'partially_paid';

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'balance' => $newBalance,
                'status' => $newStatus,
            ]);

            AuditLogger::log('payment_received', $payment, null, [
                'receipt_no' => $receiptNo,
                'amount' => $amount,
                'invoice_balance' => $newBalance,
            ]);

            // Dispatched after commit: a receipt-email failure never rolls back the payment.
            try {
                PaymentRecorded::dispatch($payment);
            } catch (Throwable $exception) {
                report($exception);
            }

            return $payment;
        });
    }

    /**
     * Reverse a payment (SOP PR-5, TC-FIN-18). The original payment row is RETAINED and flagged;
     * an offsetting payment_reversals row records who, when, why and how much. The invoice
     * balance is recomputed from the remaining valid payments inside the same transaction.
     * Nothing is ever deleted.
     */
    public static function reversePayment(Payment $payment, string $reason, ?int $reversedBy = null): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reversal_reason' => 'A reason is required to reverse a payment.',
            ]);
        }

        DB::transaction(function () use ($payment, $reason, $reversedBy) {
            /** @var Payment $locked */
            $locked = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($locked->isReversed()) {
                throw ValidationException::withMessages([
                    'reversal' => 'This payment has already been reversed.',
                ]);
            }

            /** @var Invoice $invoice */
            $invoice = Invoice::lockForUpdate()->findOrFail($locked->invoice_id);

            $locked->update([
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            PaymentReversal::create([
                'payment_id' => $locked->id,
                'invoice_id' => $invoice->id,
                'amount' => $locked->amount,
                'reason' => $reason,
                'reversed_by' => $reversedBy ?? auth()->id(),
                'reversed_at' => $locked->reversed_at,
            ]);

            // Recompute invoice amounts from all non-reversed payments
            $newAmountPaid = '0.00';
            foreach (Payment::where('invoice_id', $invoice->id)->whereNull('reversed_at')->pluck('amount') as $amount) {
                $newAmountPaid = Money::add($newAmountPaid, $amount);
            }

            $newBalance = Money::sub($invoice->total, $newAmountPaid);

            $newStatus = 'issued';
            if (Money::comp($newAmountPaid, '0.00') > 0) {
                $newStatus = Money::comp($newBalance, '0.00') <= 0 ? 'paid' : 'partially_paid';
            }

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'balance' => $newBalance,
                'status' => $newStatus,
            ]);

            AuditLogger::log('payment_reversed', $locked, null, [
                'receipt_no' => $locked->receipt_no,
                'amount' => $locked->amount,
                'reason' => $reason,
                'restored_balance' => $newBalance,
            ]);
        });

        $payment->refresh();
    }
}
