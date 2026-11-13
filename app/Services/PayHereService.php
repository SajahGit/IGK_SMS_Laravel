<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PayHereTransaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayHereService
{
    public function createTransaction(Invoice $invoice): PayHereTransaction
    {
        $this->ensureSandboxEnabled();

        if (! config('services.payhere.merchant_id') || ! config('services.payhere.merchant_secret')) {
            throw ValidationException::withMessages(['payment' => 'PayHere is not configured. Contact the school office.']);
        }

        $currency = strtoupper((string) config('services.payhere.currency', 'LKR'));
        if (strtoupper($invoice->currency) !== $currency) {
            throw ValidationException::withMessages([
                'payment' => "PayHere is configured for {$currency} only. Contact the school office to arrange payment for this invoice currency.",
            ]);
        }

        return DB::transaction(function () use ($invoice): PayHereTransaction {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if (! in_array($lockedInvoice->status, ['issued', 'partially_paid'], true)
                || Money::comp($lockedInvoice->balance, '0.00') <= 0) {
                throw ValidationException::withMessages(['invoice' => 'Only an unpaid, issued invoice can be paid online.']);
            }

            $currency = (string) config('services.payhere.currency', 'LKR');
            $pending = PayHereTransaction::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($pending) {
                if (Money::comp($pending->amount, $lockedInvoice->balance) !== 0 || $pending->currency !== $currency) {
                    throw ValidationException::withMessages([
                        'payment' => 'A previous online payment is still pending. Contact the school office before trying again.',
                    ]);
                }

                return $pending;
            }

            return PayHereTransaction::create([
                'invoice_id' => $lockedInvoice->id,
                'order_id' => 'PH-'.$lockedInvoice->id.'-'.Str::upper(Str::random(16)),
                'amount' => $lockedInvoice->balance,
                'currency' => $currency,
                'status' => 'pending',
            ]);
        });
    }

    public function checkoutHash(string $orderId, string $amount, string $currency): string
    {
        $merchantId = (string) config('services.payhere.merchant_id');
        $merchantSecretHash = strtoupper(md5((string) config('services.payhere.merchant_secret')));

        return strtoupper(md5($merchantId.$orderId.number_format((float) $amount, 2, '.', '').$currency.$merchantSecretHash));
    }

    public function verifyNotification(array $payload): bool
    {
        foreach (['merchant_id', 'order_id', 'payhere_amount', 'payhere_currency', 'status_code', 'md5sig'] as $field) {
            if (! isset($payload[$field]) || ! is_scalar($payload[$field]) || (string) $payload[$field] === '') {
                return false;
            }
        }

        $merchantId = (string) config('services.payhere.merchant_id');
        $secret = (string) config('services.payhere.merchant_secret');
        if ($merchantId === '' || $secret === '' || (string) $payload['merchant_id'] !== $merchantId) {
            return false;
        }
        if (! is_numeric($payload['payhere_amount'])
            || ! Money::hasValidScale((string) $payload['payhere_amount'])
            || Money::comp((string) $payload['payhere_amount'], '0.00') <= 0
            || strlen((string) $payload['payhere_currency']) !== 3) {
            return false;
        }

        $secretHash = strtoupper(md5($secret));
        $expected = strtoupper(md5(
            $merchantId.
            ($payload['order_id'] ?? '').
            ($payload['payhere_amount'] ?? '').
            ($payload['payhere_currency'] ?? '').
            ($payload['status_code'] ?? '').
            $secretHash
        ));

        return hash_equals($expected, strtoupper((string) $payload['md5sig']));
    }

    public function handleNotification(array $payload): void
    {
        if (! config('services.payhere.enabled') || ! config('services.payhere.sandbox')) {
            abort(503, 'PayHere sandbox payments are disabled.');
        }

        if (! $this->verifyNotification($payload)) {
            abort(400, 'Invalid PayHere notification signature.');
        }

        DB::transaction(function () use ($payload): void {
            $transaction = PayHereTransaction::query()
                ->where('order_id', $payload['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->status === 'paid') {
                return;
            }
            if (Money::comp($transaction->amount, $payload['payhere_amount']) !== 0
                || $transaction->currency !== $payload['payhere_currency']) {
                abort(400, 'PayHere notification amount or currency does not match the order.');
            }

            $transaction->notification = $payload;
            if ((string) $payload['status_code'] !== '2') {
                $transaction->status = 'failed';
                $transaction->payment_id = $payload['payment_id'] ?? null;
                $transaction->save();

                return;
            }

            $paymentId = trim((string) ($payload['payment_id'] ?? ''));
            if ($paymentId === '' || PayHereTransaction::query()->where('payment_id', $paymentId)->whereKeyNot($transaction->id)->exists()) {
                abort(400, 'PayHere payment ID is missing or already used.');
            }

            PaymentService::recordPayment(
                $transaction->invoice_id,
                $transaction->amount,
                'payhere',
                $paymentId,
                today()->toDateString()
            );

            $transaction->payment_id = $paymentId;
            $transaction->status = 'paid';
            $transaction->save();
        });
    }

    public function checkoutUrl(): string
    {
        $this->ensureSandboxEnabled();

        return 'https://sandbox.payhere.lk/pay/checkout';
    }

    private function ensureSandboxEnabled(): void
    {
        if (! config('services.payhere.enabled') || ! config('services.payhere.sandbox')) {
            throw ValidationException::withMessages([
                'payment' => 'PayHere sandbox payments are disabled.',
            ]);
        }
    }
}
