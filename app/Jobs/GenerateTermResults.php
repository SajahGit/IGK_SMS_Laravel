<?php

namespace App\Jobs;

use App\Models\Term;
use App\Models\TermResultGeneration;
use App\Services\TermResultService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateTermResults implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $termId,
        public int $classId
    ) {}

    public function handle(TermResultService $service): void
    {
        $generation = TermResultGeneration::query()
            ->where('term_id', $this->termId)
            ->where('class_id', $this->classId)
            ->firstOrFail();
        $generation->update([
            'status' => 'running',
            'error' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        try {
            $service->generate(Term::findOrFail($this->termId), $this->classId);
            $generation->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $generation->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 4000),
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        TermResultGeneration::query()
            ->where('term_id', $this->termId)
            ->where('class_id', $this->classId)
            ->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 4000),
                'completed_at' => now(),
            ]);
    }
}
