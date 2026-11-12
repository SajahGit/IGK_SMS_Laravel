<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBulkCsvImport;
use App\Services\BulkCsvImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Automatic data entry from the command line / cron.
 *   php artisan import:csv students storage/app/imports/students.csv            (validate only)
 *   php artisan import:csv inventory public/sample-data/inventory.csv --commit   (validate + import)
 *   php artisan import:csv guardians path.csv --commit --queue                   (run on the queue worker)
 */
class ImportCsv extends Command
{
    protected $signature = 'import:csv {entity : students|staff|subjects|guardians|inventory|library_books} {path : CSV file path} {--commit : Save the rows (otherwise dry run)} {--queue : Run the import on the queue worker}';

    protected $description = 'Validate and optionally import a CSV file (all-or-nothing)';

    public function handle(BulkCsvImportService $importer): int
    {
        $entity = (string) $this->argument('entity');
        $path = (string) $this->argument('path');
        if (! is_file($path)) {
            $path = base_path($path);
        }

        if ($this->option('queue')) {
            if (! $this->option('commit')) {
                $this->error('--queue requires --commit.');

                return self::FAILURE;
            }
            $runId = (string) Str::uuid();
            ProcessBulkCsvImport::dispatch($entity, $path, $runId);
            $this->info("Import queued. Run id: {$runId}");

            return self::SUCCESS;
        }

        try {
            $result = $importer->inspectPath($entity, $path);
        } catch (InvalidArgumentException|\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($result['file_errors'] as $error) {
            $this->error($error);
        }
        foreach ($result['rows'] as $row) {
            foreach ($row['errors'] as $error) {
                $this->warn("Line {$row['line']}: {$error}");
            }
        }
        $this->line("{$result['valid_count']} valid, {$result['invalid_count']} invalid.");

        if (! $result['can_import']) {
            $this->error('Nothing was imported.');

            return self::FAILURE;
        }
        if (! $this->option('commit')) {
            $this->info('Dry run passed. Re-run with --commit to save.');

            return self::SUCCESS;
        }

        $count = $importer->import($entity, $result['valid_rows']);
        $this->info("{$count} row(s) imported.");

        return self::SUCCESS;
    }
}
