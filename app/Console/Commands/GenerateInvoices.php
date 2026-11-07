<?php

namespace App\Console\Commands;

use App\Models\FeeStructure;
use App\Models\Term;
use App\Services\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class GenerateInvoices extends Command
{
    protected $signature = 'school:generate-invoices
        {class_id : ID of the class to invoice}
        {--term= : Term ID (defaults to the current term)}
        {--due-days=30 : Days from today until the invoices are due}';

    protected $description = 'Generate invoices for every student of a class using its active fee structures (idempotent)';

    public function handle(): int
    {
        $classId = (int) $this->argument('class_id');
        $termId = $this->option('term') !== null
            ? (int) $this->option('term')
            : Term::current()->value('id');

        $feeIds = FeeStructure::where('class_id', $classId)
            ->where('is_active', true)
            ->where(function ($q) use ($termId) {
                $q->whereNull('term_id');
                if ($termId) {
                    $q->orWhere('term_id', $termId);
                }
            })
            ->pluck('id')
            ->all();

        try {
            $result = InvoiceService::bulkGenerateForClass(
                $classId,
                $termId ?: null,
                now()->toDateString(),
                now()->addDays((int) $this->option('due-days'))->toDateString(),
                $feeIds
            );
        } catch (ValidationException $e) {
            $this->error(collect($e->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }

        $this->info("Created {$result['created']}, skipped {$result['skipped']} already invoiced, of {$result['total']} student(s).");

        return self::SUCCESS;
    }
}
