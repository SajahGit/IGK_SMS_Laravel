<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Term;
use App\Models\TermResult;
use Illuminate\Console\Command;

class CloseTerm extends Command
{
    protected $signature = 'school:close-term
        {term_id? : Term ID (defaults to the current term)}
        {--force : Close even if results are not all published}';

    protected $description = 'Close a term: refuses while results are unpublished unless --force; reports open invoices';

    public function handle(): int
    {
        $term = $this->argument('term_id')
            ? Term::find($this->argument('term_id'))
            : Term::current()->first();

        if (! $term) {
            $this->error('Term not found.');

            return self::FAILURE;
        }

        $unpublished = TermResult::where('term_id', $term->id)
            ->where('publication_status', '!=', 'published')
            ->count();

        if ($unpublished > 0 && ! $this->option('force')) {
            $this->error("{$unpublished} term result(s) are not published. Publish them first or use --force.");

            return self::FAILURE;
        }

        $openInvoices = Invoice::where('term_id', $term->id)
            ->whereIn('status', ['issued', 'partially_paid'])
            ->count();

        $term->update(['is_current' => false]);

        $this->info("Term '{$term->name}' closed.");
        if ($openInvoices > 0) {
            $this->warn("{$openInvoices} invoice(s) for this term are still unpaid; they stay collectable.");
        }

        return self::SUCCESS;
    }
}
