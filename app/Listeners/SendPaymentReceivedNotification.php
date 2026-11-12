<?php

namespace App\Listeners;

use App\Events\PaymentRecorded;
use App\Notifications\PaymentReceivedNotification;
use Throwable;

class SendPaymentReceivedNotification
{
    public function handle(PaymentRecorded $event): void
    {
        try {
            $studentUser = $event->payment->invoice?->student?->user;
            if ($studentUser) {
                $studentUser->notify(new PaymentReceivedNotification($event->payment));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
