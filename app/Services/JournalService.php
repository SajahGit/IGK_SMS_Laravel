<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class JournalService
{
    /**
     * Post a balanced journal entry.
     *
     * @param  array{entry_date:string,description:string,reference?:?string,branch_id?:?int}  $data
     * @param  array<int,array{account_id:int,debit?:string|float|int,credit?:string|float|int,memo?:?string}>  $lines
     */
    public static function post(array $data, array $lines, ?string $sourceType = null, ?int $sourceId = null): JournalEntry
    {
        $currency = strtoupper($data['currency'] ?? config('school.default_currency', 'LKR'));
        if (! in_array($currency, config('school.currencies', ['LKR']), true)) {
            throw new InvalidArgumentException('Journal currency must be one of the configured school currencies.');
        }

        $debit = '0.00';
        $credit = '0.00';
        foreach ($lines as $line) {
            $debit = bcadd($debit, (string) ($line['debit'] ?? 0), 2);
            $credit = bcadd($credit, (string) ($line['credit'] ?? 0), 2);
        }

        if (count($lines) < 2 || bccomp($debit, '0.00', 2) <= 0 || bccomp($debit, $credit, 2) !== 0) {
            throw new InvalidArgumentException('Journal entry must have at least two lines and total debits must equal total credits.');
        }

        return DB::transaction(function () use ($data, $lines, $sourceType, $sourceId, $currency) {
            $entry = JournalEntry::create([
                'entry_no' => 'TMP'.uniqid(),
                'entry_date' => $data['entry_date'],
                'currency' => $currency,
                'description' => $data['description'],
                'reference' => $data['reference'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);
            $entry->update(['entry_no' => 'JV-'.date('Y').'-'.str_pad((string) $entry->id, 5, '0', STR_PAD_LEFT)]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    /** Auto-post journal entries for issued invoices and non-reversed payments (idempotent). */
    public static function syncFromBilling(): array
    {
        $acc = fn (string $code) => Account::where('code', $code)->value('id');
        $cash = $acc('1000');
        $bank = $acc('1010');
        $receivable = $acc('1100');
        $feeIncome = $acc('4000');

        if (! $cash || ! $bank || ! $receivable || ! $feeIncome) {
            throw new InvalidArgumentException('Chart of accounts is missing the default fee accounts (1000, 1010, 1100, 4000).');
        }

        $posted = ['invoices' => 0, 'payments' => 0];

        Invoice::query()->whereNotIn('status', ['draft', 'cancelled'])->orderBy('id')->each(function (Invoice $invoice) use (&$posted, $receivable, $feeIncome) {
            if (JournalEntry::where('source_type', 'invoice')->where('source_id', $invoice->id)->exists()) {
                return;
            }
            self::post([
                'entry_date' => $invoice->issue_date?->toDateString() ?? now()->toDateString(),
                'description' => 'Fee invoice '.$invoice->invoice_no,
                'reference' => $invoice->invoice_no,
                'currency' => $invoice->currency,
                'branch_id' => $invoice->branch_id ?? null,
            ], [
                ['account_id' => $receivable, 'debit' => $invoice->total, 'memo' => 'Student fees receivable'],
                ['account_id' => $feeIncome, 'credit' => $invoice->total, 'memo' => 'Fee income'],
            ], 'invoice', $invoice->id);
            $posted['invoices']++;
        });

        Payment::query()->whereNull('reversed_at')->with('invoice')->orderBy('id')->each(function (Payment $payment) use (&$posted, $cash, $bank, $receivable) {
            if (JournalEntry::where('source_type', 'payment')->where('source_id', $payment->id)->exists()) {
                return;
            }
            self::post([
                'entry_date' => $payment->paid_at?->toDateString() ?? now()->toDateString(),
                'description' => 'Fee receipt '.$payment->receipt_no,
                'reference' => $payment->receipt_no,
                'currency' => $payment->currency,
                'branch_id' => $payment->invoice?->branch_id ?? null,
            ], [
                ['account_id' => $payment->method === 'cash' ? $cash : $bank, 'debit' => $payment->amount, 'memo' => ucfirst(str_replace('_', ' ', (string) $payment->method))],
                ['account_id' => $receivable, 'credit' => $payment->amount, 'memo' => 'Settle student receivable'],
            ], 'payment', $payment->id);
            $posted['payments']++;
        });

        return $posted;
    }
}
