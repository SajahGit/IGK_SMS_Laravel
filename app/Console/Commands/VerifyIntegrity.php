<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Console\Command;

class VerifyIntegrity extends Command
{
    protected $signature = 'school:verify-integrity';

    protected $description = 'Read-only check that invoice totals, balances and payments are consistent (SOP §12)';

    public function handle(): int
    {
        $problems = [];
        $checked = 0;

        Invoice::where('status', '!=', 'cancelled')->chunkById(500, function ($invoices) use (&$problems, &$checked) {
            foreach ($invoices as $invoice) {
                $checked++;

                $paid = '0.00';
                foreach (Payment::where('invoice_id', $invoice->id)->whereNull('reversed_at')->pluck('amount') as $amount) {
                    $paid = Money::add($paid, $amount);
                }

                $expectedBalance = Money::sub($invoice->total, $paid);

                if (Money::comp($invoice->amount_paid, $paid) !== 0) {
                    $problems[] = "{$invoice->invoice_no}: amount_paid {$invoice->amount_paid} != sum of valid payments {$paid}";
                }
                if (Money::comp($invoice->balance, $expectedBalance) !== 0) {
                    $problems[] = "{$invoice->invoice_no}: balance {$invoice->balance} != total - payments ({$expectedBalance})";
                }
                if (Money::comp(Money::sub($invoice->subtotal, $invoice->discount), $invoice->total) !== 0) {
                    $problems[] = "{$invoice->invoice_no}: subtotal {$invoice->subtotal} - discount {$invoice->discount} != total {$invoice->total}";
                }

                $itemSum = '0.00';
                foreach (InvoiceItem::where('invoice_id', $invoice->id)->pluck('amount') as $amount) {
                    $itemSum = Money::add($itemSum, $amount);
                }
                if (Money::comp($itemSum, $invoice->total) !== 0) {
                    $problems[] = "{$invoice->invoice_no}: invoice lines add up to {$itemSum} but total is {$invoice->total}";
                }

                if (Money::comp($invoice->balance, '0.00') < 0) {
                    $problems[] = "{$invoice->invoice_no}: negative balance {$invoice->balance}";
                }

                $expectedStatus = Money::comp($paid, '0.00') <= 0
                    ? 'issued'
                    : (Money::comp($expectedBalance, '0.00') <= 0 ? 'paid' : 'partially_paid');
                // A draft with no payments is a valid state of its own.
                if ($invoice->status === 'draft' && Money::comp($paid, '0.00') <= 0) {
                    $expectedStatus = 'draft';
                }
                if ($invoice->status !== $expectedStatus) {
                    $problems[] = "{$invoice->invoice_no}: status '{$invoice->status}' should be '{$expectedStatus}'";
                }
            }
        });

        $this->info("Checked {$checked} invoice(s).");

        if ($problems === []) {
            $this->info('No integrity problems found.');

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->error($problem);
        }
        $this->error(count($problems).' problem(s) found. Nothing was changed.');

        return self::FAILURE;
    }
}
