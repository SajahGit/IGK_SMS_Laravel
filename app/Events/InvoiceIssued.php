<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after an invoice is issued. Dispatched after the DB commit, so a listener
 * failure can never roll back the money write (SOP §10.1 Events).
 */
class InvoiceIssued implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Invoice $invoice) {}
}
