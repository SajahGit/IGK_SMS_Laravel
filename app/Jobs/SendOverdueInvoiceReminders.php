<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Notifications\InvoiceOverdueNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Background job: reminds students and linked guardians about overdue invoices.
 * Each invoice is reminded at most once every $repeatAfterDays days.
 */
class SendOverdueInvoiceReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $repeatAfterDays = 7) {}

    public function handle(): void
    {
        Invoice::query()
            ->with(['student.user', 'student.guardians.user'])
            ->whereIn('status', ['issued', 'partially_paid'])
            ->whereDate('due_date', '<', today())
            ->where('balance', '>', 0)
            ->chunkById(200, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $key = 'invoice-overdue-reminder:'.$invoice->id;
                    if (! Cache::add($key, true, now()->addDays(max(1, $this->repeatAfterDays)))) {
                        continue;
                    }

                    $student = $invoice->student;
                    if (! $student) {
                        continue;
                    }

                    $student->user?->notify(new InvoiceOverdueNotification($invoice));
                    foreach ($student->guardians as $guardian) {
                        $guardian->user?->notify(new InvoiceOverdueNotification($invoice));
                    }
                }
            });
    }
}
