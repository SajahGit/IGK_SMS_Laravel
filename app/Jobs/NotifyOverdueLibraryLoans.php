<?php

namespace App\Jobs;

use App\Models\LibraryLoan;
use App\Notifications\LibraryLoanOverdueNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Background job: tells students about unreturned library loans past their due date.
 * Fines are still assessed when the copy is returned (one fine per loan).
 */
class NotifyOverdueLibraryLoans implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $repeatAfterDays = 3) {}

    public function handle(): void
    {
        LibraryLoan::query()
            ->with(['copy.book', 'student.user'])
            ->whereNull('returned_at')
            ->whereDate('due_at', '<', today())
            ->chunkById(200, function ($loans) {
                foreach ($loans as $loan) {
                    $key = 'library-overdue-reminder:'.$loan->id;
                    if (! Cache::add($key, true, now()->addDays(max(1, $this->repeatAfterDays)))) {
                        continue;
                    }
                    $loan->student?->user?->notify(new LibraryLoanOverdueNotification($loan));
                }
            });
    }
}
