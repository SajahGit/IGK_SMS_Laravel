<?php

namespace App\Console\Commands;

use App\Services\AcademicLifecycleService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SyncAcademicLifecycle extends Command
{
    protected $signature = 'school:sync-lifecycle {--simulate-date= : Run the lifecycle as of YYYY-MM-DD (local/testing only)}';

    protected $description = 'Synchronize academic years, terms, and related lifecycle data';

    public function handle(AcademicLifecycleService $lifecycle): int
    {
        $simulation = $this->option('simulate-date');
        if ($simulation !== null && ! app()->environment(['local', 'testing'])) {
            $this->error('The --simulate-date option is only available in local and testing environments.');

            return self::FAILURE;
        }

        if ($simulation !== null) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $simulation)) {
                $this->error('The simulated date must use YYYY-MM-DD format.');

                return self::FAILURE;
            }

            [$year, $month, $day] = array_map('intval', explode('-', $simulation));
            if (! checkdate($month, $day, $year)) {
                $this->error('The simulated date is not a valid calendar date.');

                return self::FAILURE;
            }
        }

        $date = $simulation
            ? CarbonImmutable::parse($simulation, config('app.timezone'))
            : CarbonImmutable::today(config('app.timezone'));

        $result = $lifecycle->sync($date);
        $this->info(sprintf(
            'Academic lifecycle synchronized for %s: %s / %s.',
            $date->toDateString(),
            $result['academic_year'] ?? 'no active academic year',
            $result['term'] ?? 'no active term'
        ));

        return self::SUCCESS;
    }
}
