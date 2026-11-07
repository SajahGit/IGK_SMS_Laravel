<?php

namespace App\Jobs;

use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Bulk invoice generation for one class and term (US-F1, SOP §10.1 Jobs).
 *
 * Atomic: InvoiceService::bulkGenerateForClass() runs in one transaction, so a failure
 * leaves no half batch. Idempotent: a retry or a second run skips already-invoiced
 * students, so $tries = 3 is safe.
 *
 * The outcome is stored in the cache for one hour under invoice-bulk:{batchKey}
 * so a screen can show "30 skipped (already invoiced)". Read it with
 * GenerateTermInvoices::result($batchKey).
 */
class GenerateTermInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<int, int|string>  $feeStructureIds
     */
    public function __construct(
        public int $classId,
        public ?int $termId,
        public string $issueDate,
        public string $dueDate,
        public array $feeStructureIds,
        public string $batchKey
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        try {
            $result = InvoiceService::bulkGenerateForClass(
                $this->classId,
                $this->termId,
                $this->issueDate,
                $this->dueDate,
                $this->feeStructureIds
            );
        } catch (ValidationException $exception) {
            // Bad input will never succeed on retry: record it and stop.
            $this->store([
                'status' => 'failed',
                'message' => collect($exception->errors())->flatten()->implode(' '),
            ]);

            return;
        }

        $this->store($result + [
            'status' => 'completed',
            'message' => "{$result['created']} created, {$result['skipped']} skipped (already invoiced).",
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->store([
            'status' => 'failed',
            'message' => mb_substr($exception->getMessage(), 0, 500),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function result(string $batchKey): ?array
    {
        return Cache::get(self::cacheKey($batchKey));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function store(array $payload): void
    {
        Cache::put(self::cacheKey($this->batchKey), $payload, now()->addHour());
    }

    private static function cacheKey(string $batchKey): string
    {
        return "invoice-bulk:{$batchKey}";
    }
}
