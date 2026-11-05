<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a payment is recorded. Dispatched after the DB commit, so a listener
 * failure can never roll back the money write (SOP §10.1 Events).
 */
class PaymentRecorded implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Payment $payment) {}
}
