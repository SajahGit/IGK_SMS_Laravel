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
            $student = $event->payment->invoice?->student;
            $studentUser = $student?->user;
            if ($studentUser) {
                $studentUser->notify(new PaymentReceivedNotification($event->payment));
            }
            $guardianUser = $student?->guardian?->user;
            if ($guardianUser && $guardianUser->id !== $studentUser?->id) {
                $guardianUser->notify(new PaymentReceivedNotification($event->payment));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
