<?php

namespace App\Support;

class NumberToWords
{
    private const ONES = [
        'zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
        'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
        'seventeen', 'eighteen', 'nineteen',
    ];

    private const TENS = [
        2 => 'twenty',
        3 => 'thirty',
        4 => 'forty',
        5 => 'fifty',
        6 => 'sixty',
        7 => 'seventy',
        8 => 'eighty',
        9 => 'ninety',
    ];

    public static function rupees(string|float|int $amount): string
    {
        $formatted = number_format((float) $amount, 2, '.', '');
        [$rupees, $cents] = explode('.', $formatted);

        return self::integer((int) $rupees).' rupees and '.self::integer((int) $cents).' cents only';
    }

    private static function integer(int $number): string
    {
        if ($number < 20) {
            return self::ONES[$number];
        }

        if ($number < 100) {
            $words = self::TENS[intdiv($number, 10)];
            $remainder = $number % 10;

            return $remainder === 0 ? $words : $words.'-'.self::ONES[$remainder];
        }

        foreach ([10000000 => 'crore', 100000 => 'lakh', 1000 => 'thousand', 100 => 'hundred'] as $scale => $unit) {
            if ($number >= $scale) {
                $remainder = $number % $scale;
                $words = self::integer(intdiv($number, $scale)).' '.$unit;

                return $remainder === 0 ? $words : $words.' '.self::integer($remainder);
            }
        }

        return self::ONES[0];
    }
}
