<?php

namespace App\Support;

class Money
{
    public const SCALE = 2;

    public static function add(string|float|int|null $a, string|float|int|null $b): string
    {
        return bcadd(self::toBcmathString($a), self::toBcmathString($b), self::SCALE);
    }

    public static function sub(string|float|int|null $a, string|float|int|null $b): string
    {
        return bcsub(self::toBcmathString($a), self::toBcmathString($b), self::SCALE);
    }

    public static function mul(string|float|int|null $a, string|float|int|null $b): string
    {
        return bcmul(self::toBcmathString($a), self::toBcmathString($b), self::SCALE);
    }

    public static function div(string|float|int|null $a, string|float|int|null $b): string
    {
        $bStr = self::toBcmathString($b);
        if (bccomp($bStr, '0.00', self::SCALE) === 0) {
            return '0.00';
        }

        return bcdiv(self::toBcmathString($a), $bStr, self::SCALE);
    }

    public static function comp(string|float|int|null $a, string|float|int|null $b): int
    {
        return bccomp(self::toBcmathString($a), self::toBcmathString($b), self::SCALE);
    }

    /**
     * True when the value is a plain non-negative amount with at most two decimals
     * (e.g. "500", "500.5", "500.00"). Money::add/comp round silently, so input that
     * would be rounded (500.004) must be rejected BEFORE it reaches the database.
     */
    public static function hasValidScale(string|float|int|null $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return preg_match('/^\d{1,10}(\.\d{1,2})?$/', trim((string) $value)) === 1;
    }

    public static function format(string|float|int|null $amount, string $currency = 'LKR '): string
    {
        $val = (float) self::toBcmathString($amount);

        return $currency.number_format($val, 2);
    }

    public static function sumByCurrency(iterable $records, string $amountColumn): array
    {
        $totals = [];
        foreach ($records as $record) {
            $currency = strtoupper(data_get($record, 'currency') ?: config('school.default_currency', 'LKR'));
            $totals[$currency] = self::add($totals[$currency] ?? '0.00', data_get($record, $amountColumn));
        }
        ksort($totals);

        return $totals;
    }

    public static function formatTotals(array $totals): string
    {
        if ($totals === []) {
            return self::format('0.00', config('school.default_currency', 'LKR').' ');
        }

        $formatted = [];
        foreach ($totals as $currency => $amount) {
            $formatted[] = self::format($amount, $currency.' ');
        }

        return implode(' / ', $formatted);
    }

    public static function hasPositiveTotal(array $totals): bool
    {
        foreach ($totals as $amount) {
            if (self::comp($amount, '0.00') > 0) {
                return true;
            }
        }

        return false;
    }

    private static function toBcmathString(string|float|int|null $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        return number_format((float) $value, self::SCALE, '.', '');
    }
}
