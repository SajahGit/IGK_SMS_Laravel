<?php

namespace App\Jobs;

use App\Models\PayrollRun;
use App\Models\User;
use App\Services\HrPayrollService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Background job: creates the DRAFT payroll run for a calendar month (default: previous month).
 * Approval and payment remain manual steps for authorised users.
 */
class GenerateMonthlyPayroll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public ?string $month = null) {}

    public function handle(): void
    {
        $base = $this->month ? Carbon::createFromFormat('Y-m', $this->month)->startOfMonth() : now()->subMonthNoOverflow()->startOfMonth();
        $start = $base->copy()->startOfMonth()->toDateString();
        $end = $base->copy()->endOfMonth()->toDateString();

        if (PayrollRun::query()->whereDate('period_start', '<=', $end)->whereDate('period_end', '>=', $start)->exists()) {
            return;
        }

        $actor = User::query()->where('is_active', true)->whereIn('role', ['admin', 'super-admin'])->orderBy('id')->first();
        if (! $actor) {
            Log::warning('Monthly payroll skipped: no active administrator to attribute the run to.');

            return;
        }

        try {
            HrPayrollService::generatePayroll($start, $end, $actor->id);
        } catch (ValidationException $exception) {
            Log::warning('Monthly payroll not generated: '.collect($exception->errors())->flatten()->implode(' '));
        }
    }
}
