<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Race-free, gapless counters (SOP §11: "receipt_no generated under lockForUpdate").
 *
 * Locking only the existing payment/invoice rows does not stop two cashiers who pay
 * DIFFERENT invoices at the same moment from reading the same "highest number", so every
 * number is drawn from one dedicated row in number_sequences that is locked FOR UPDATE.
 *
 * The increment lives in the caller's transaction (nested transactions are savepoints):
 * if the payment/invoice that asked for the number rolls back, the counter rolls back
 * with it, so no number is ever burned and the series stays gapless.
 */
class NumberSequence
{
    /**
     * @param  callable|null  $floor  returns the highest number already used outside the counter
     *                                (legacy rows / rows inserted by hand); evaluated AFTER the lock is held
     */
    public static function next(string $name, ?callable $floor = null): int
    {
        return DB::transaction(function () use ($name, $floor) {
            DB::table('number_sequences')->insertOrIgnore([
                'name' => $name,
                'last_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('number_sequences')->where('name', $name)->lockForUpdate()->first();

            $current = max((int) $row->last_value, $floor ? (int) $floor() : 0);
            $next = $current + 1;

            DB::table('number_sequences')->where('name', $name)->update([
                'last_value' => $next,
                'updated_at' => now(),
            ]);

            return $next;
        });
    }

    /**
     * Highest numeric suffix among values like PREFIX-000123 (shorter strings sort first,
     * so INV-2026-9999 < INV-2026-10000 even though plain string order says otherwise).
     */
    public static function highestSuffix(string $modelClass, string $column, string $prefix): int
    {
        $value = $modelClass::query()
            ->where($column, 'like', $prefix.'%')
            ->orderByRaw('LENGTH('.$column.') desc')
            ->orderByDesc($column)
            ->value($column);

        if ($value === null) {
            return 0;
        }

        $parts = explode('-', (string) $value);

        return (int) end($parts);
    }
}
