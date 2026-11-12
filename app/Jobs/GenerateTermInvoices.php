<?php

namespace App\Jobs;

use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateTermInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RESULT_TTL_MINUTES = 1440;

    public function __construct(
        public int $classId,
        public ?int $termId,
        public string $issueDate,
        public string $dueDate,
        public array $feeStructureIds,
        public string $runId
    ) {}

    public function handle(): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'running',
            'message' => 'Invoice generation is running.',
        ], now()->addMinutes(self::RESULT_TTL_MINUTES));

        try {
            $counts = InvoiceService::bulkGenerateForClass(
                $this->classId,
                $this->termId,
                $this->issueDate,
                $this->dueDate,
                $this->feeStructureIds
            );
            Cache::put($this->cacheKey(), [
                'status' => 'completed',
                'message' => "{$counts['created']} created, {$counts['skipped']} skipped (already invoiced).",
                ...$counts,
            ], now()->addMinutes(self::RESULT_TTL_MINUTES));
        } catch (Throwable $exception) {
            Cache::put($this->cacheKey(), [
                'status' => 'failed',
                'message' => 'Invoice generation failed.',
                'error' => $exception->getMessage(),
            ], now()->addMinutes(self::RESULT_TTL_MINUTES));

            throw $exception;
        }
    }

    public static function result(string $runId): ?array
    {
        return Cache::get('invoice-generation:'.$runId);
    }

    private function cacheKey(): string
    {
        return 'invoice-generation:'.$this->runId;
    }
}
