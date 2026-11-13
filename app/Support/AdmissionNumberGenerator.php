<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionNumberGenerator
{
    public static function next(int $programId, int $academicYearId): string
    {
        return DB::transaction(function () use ($programId, $academicYearId) {
            $program = Program::whereKey($programId)->lockForUpdate()->firstOrFail();
            $academicYear = AcademicYear::whereKey($academicYearId)->lockForUpdate()->firstOrFail();
            $prefix = self::formatPrefix($program, $academicYear);
            $numbers = Student::where('admission_no', 'like', $prefix.'%')
                ->lockForUpdate()
                ->pluck('admission_no');
            $maxNumber = $numbers->map(function ($number) use ($prefix) {
                $suffix = substr((string) $number, strlen($prefix));

                return ctype_digit($suffix) ? (int) $suffix : 0;
            })->max() ?? 0;

            return $prefix.str_pad((string) ($maxNumber + 1), 4, '0', STR_PAD_LEFT);
        });
    }

    public static function prefix(int $programId, int $academicYearId): string
    {
        return self::formatPrefix(
            Program::findOrFail($programId),
            AcademicYear::findOrFail($academicYearId)
        );
    }

    private static function formatPrefix(Program $program, AcademicYear $academicYear): string
    {
        $programCode = strtoupper((string) $program->code);
        $normalizedCode = self::normalizedProgramCode($programCode);

        if ($normalizedCode === null) {
            throw ValidationException::withMessages([
                'program_id' => 'The selected program code must contain at least two letters and use only letters or hyphens.',
            ]);
        }

        $hasCollision = Program::query()
            ->whereKeyNot($program->getKey())
            ->get(['code'])
            ->contains(fn (Program $candidate) => self::normalizedProgramCode(strtoupper((string) $candidate->code)) === $normalizedCode);

        if ($hasCollision) {
            throw ValidationException::withMessages([
                'program_id' => 'The selected program code conflicts with another program for admission numbering.',
            ]);
        }

        return $academicYear->start_date->format('Y')."-{$normalizedCode}-";
    }

    private static function normalizedProgramCode(string $programCode): ?string
    {
        if (! preg_match('/^[A-Z]+(?:-[A-Z]+)*$/', $programCode)) {
            return null;
        }

        $letters = str_replace('-', '', $programCode);

        if (strlen($letters) < 2) {
            return null;
        }

        return substr($letters, 0, 6);
    }
}
