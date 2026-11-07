<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Notifications\InvoiceIssuedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

/**
 * Queued and idempotent: handling the same event twice sends one notification.
 * A failure is reported, never thrown, so it cannot touch the invoice that was committed.
 */
class NotifyStudentOfInvoice implements ShouldQueue
{
    public int $tries = 3;

    public function handle(InvoiceIssued $event): void
    {
        try {
            $invoice = $event->invoice->loadMissing('student.user');
            $user = $invoice->student?->user;

            if (! $user) {
                return;
            }

            $alreadySent = $user->notifications()
                ->where('type', InvoiceIssuedNotification::class)
                ->latest()->limit(100)->get()
                ->contains(fn ($notification) => ($notification->data['invoice_id'] ?? null) === $invoice->id);

            if (! $alreadySent) {
                $user->notify(new InvoiceIssuedNotification($invoice));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
