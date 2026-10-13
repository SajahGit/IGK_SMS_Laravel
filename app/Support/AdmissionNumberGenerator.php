<?php

namespace App\Support;

use App\Models\Student;

class AdmissionNumberGenerator
{
    /** Example: ADM-2026-0001 */
    public static function next(): string
    {
        $year = now()->format('Y');
        $prefix = config('school.admission_prefix').'-'.$year.'-';

        $last = Student::where('admission_no', 'like', $prefix.'%')
            ->orderByDesc('admission_no')
            ->value('admission_no');

        $number = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
