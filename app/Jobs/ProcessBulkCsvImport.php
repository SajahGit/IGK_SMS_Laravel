<?php

namespace App\Jobs;

use App\Services\BulkCsvImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Background job: validates then imports a stored CSV (all-or-nothing). Progress is kept in cache. */
class ProcessBulkCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public string $entity, public string $path, public string $runId) {}

    public function handle(BulkCsvImportService $importer): void
    {
        $this->put(['status' => 'running', 'message' => 'Import is running.']);

        try {
            $result = $importer->inspectPath($this->entity, $this->path);
            if (! $result['can_import']) {
                $this->put([
                    'status' => 'failed',
                    'message' => 'Validation failed; nothing was imported.',
                    'invalid_count' => $result['invalid_count'],
                    'file_errors' => $result['file_errors'],
                ]);

                return;
            }

            $count = $importer->import($this->entity, $result['valid_rows']);
            $this->put(['status' => 'completed', 'message' => "{$count} row(s) imported.", 'imported' => $count]);
        } catch (Throwable $exception) {
            $this->put(['status' => 'failed', 'message' => 'Import failed.', 'error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public static function result(string $runId): ?array
    {
        return Cache::get('csv-import:'.$runId);
    }

    private function put(array $value): void
    {
        Cache::put('csv-import:'.$this->runId, $value, now()->addDay());
    }
}
