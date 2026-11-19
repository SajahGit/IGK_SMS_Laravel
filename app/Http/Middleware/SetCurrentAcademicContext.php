<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use App\Models\Term;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SetCurrentAcademicContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->runningInConsole()) {
            $this->syncLifecycleOncePerDay();

            $year = AcademicYear::where('is_current', true)->first();
            $term = Term::where('is_current', true)->first();
            view()->share('currentAcademicYear', $year);
            view()->share('currentTerm', $term);
        }

        return $next($request);
    }

    private function syncLifecycleOncePerDay(): void
    {
        if (! app()->environment('local') || app()->runningUnitTests()) {
            return;
        }

        $date = today()->toDateString();
        $lock = Cache::lock("school:lifecycle:lock:{$date}", 300);
        if (! $lock->get()) {
            return;
        }

        $runKey = "school:lifecycle:last-run:{$date}";
        try {
            if (Cache::has($runKey)) {
                return;
            }

            $exitCode = Artisan::call('school:sync-lifecycle');
            if ($exitCode === Command::SUCCESS) {
                Cache::put($runKey, true, now()->endOfDay());
            } else {
                Log::error('Automatic academic lifecycle synchronization returned a failure exit code.', [
                    'exit_code' => $exitCode,
                    'date' => $date,
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
            Log::error('Automatic academic lifecycle synchronization failed.', [
                'date' => $date,
                'message' => $exception->getMessage(),
            ]);
        } finally {
            $lock->release();
        }
    }
}
