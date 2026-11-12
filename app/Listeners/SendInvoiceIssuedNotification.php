<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Notifications\InvoiceIssuedNotification;
use Throwable;

class SendInvoiceIssuedNotification
{
    public function handle(InvoiceIssued $event): void
    {
        try {
            $studentUser = $event->invoice->student?->user;
            if ($studentUser) {
                $studentUser->notify(new InvoiceIssuedNotification($event->invoice));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
