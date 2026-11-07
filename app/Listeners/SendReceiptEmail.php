<?php

namespace App\Listeners;

use App\Events\PaymentRecorded;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

/**
 * Queued and idempotent: handling the same event twice sends one receipt.
 * A failure is reported, never thrown, so it cannot touch the payment that was committed.
 */
class SendReceiptEmail implements ShouldQueue
{
    public int $tries = 3;

    public function handle(PaymentRecorded $event): void
    {
        try {
            $payment = $event->payment->loadMissing('invoice.student.user');
            $user = $payment->invoice?->student?->user;

            if (! $user) {
                return;
            }

            $alreadySent = $user->notifications()
                ->where('type', PaymentReceivedNotification::class)
                ->latest()->limit(100)->get()
                ->contains(fn ($notification) => ($notification->data['payment_id'] ?? null) === $payment->id);

            if (! $alreadySent) {
                $user->notify(new PaymentReceivedNotification($payment));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
