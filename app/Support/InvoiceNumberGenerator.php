<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

final class InvoiceNumberGenerator
{
    public static function next(?int $year = null): string
    {
        $year = $year ?? (int) now()->format('y');
        $prefix = config('school.invoice_prefix', 'INV').'-'.str_pad((string) $year, 2, '0', STR_PAD_LEFT).'-';

        return DB::transaction(function () use ($prefix) {
            $last = Invoice::query()
                ->where('invoice_no', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('invoice_no')
                ->value('invoice_no');

            $seq = 1;
            if (is_string($last) && preg_match('/(\d{6})$/', $last, $m)) {
                $seq = ((int) $m[1]) + 1;
            }

            return $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
        });
    }
}
