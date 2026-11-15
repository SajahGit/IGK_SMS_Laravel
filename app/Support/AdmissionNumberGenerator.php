<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionNumberGenerator
{
    public static function next(int $programId, int $academicYearId, ?int $branchId = null): string
    {
        return DB::transaction(function () use ($programId, $academicYearId, $branchId) {
            $program = Program::whereKey($programId)->lockForUpdate()->firstOrFail();
            $academicYear = AcademicYear::whereKey($academicYearId)->lockForUpdate()->firstOrFail();
            $prefix = self::formatPrefix($program, $academicYear);

            $query = Student::withoutGlobalScopes()->where('admission_no', 'like', $prefix.'%');
            $numbers = $query->lockForUpdate()->pluck('admission_no');
            $maxNumber = $numbers->map(function ($number) use ($prefix) {
                $suffix = substr((string) $number, strlen($prefix));

                return ctype_digit($suffix) ? (int) $suffix : 0;
            })->max() ?? 0;

            // Also check transfer history so numbers never collide
            $transferNumbers = DB::table('branch_transfers')
                ->where('new_admission_no', 'like', $prefix.'%')
                ->pluck('new_admission_no');
            $maxTransfer = $transferNumbers->map(function ($number) use ($prefix) {
                $suffix = substr((string) $number, strlen($prefix));

                return ctype_digit($suffix) ? (int) $suffix : 0;
            })->max() ?? 0;

            $maxSeq = max($maxNumber, $maxTransfer);

            return $prefix.str_pad((string) ($maxSeq + 1), 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Generate safe branch-specific admission number sequence for registration or transfer.
     */
    public static function nextForBranch(int $branchId, ?int $programId = null, ?int $academicYearId = null): string
    {
        return DB::transaction(function () use ($branchId, $programId, $academicYearId) {
            $branch = Branch::withoutGlobalScopes()->whereKey($branchId)->lockForUpdate()->firstOrFail();
            $branchCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($branch->code ?: 'BR'.$branch->id)));

            // If academic year & program exist and branch has matching format, check program prefix
            if ($programId && $academicYearId) {
                $program = Program::whereKey($programId)->lockForUpdate()->first();
                $academicYear = AcademicYear::whereKey($academicYearId)->lockForUpdate()->first();
                if ($program && $academicYear) {
                    $normCode = self::normalizedProgramCode(strtoupper((string) $program->code));
                    if ($normCode) {
                        $prefix = $academicYear->start_date->format('Y')."-{$normCode}-";
                        $hasExisting = Student::withoutGlobalScopes()
                            ->where('branch_id', $branchId)
                            ->where('admission_no', 'like', $prefix.'%')
                            ->exists();

                        if ($hasExisting) {
                            return self::next($programId, $academicYearId, $branchId);
                        }
                    }
                }
            }

            // Standard branch-specific admission prefix: ADM-{BRANCH}-
            $prefix = "ADM-{$branchCode}-";
            $numbers = Student::withoutGlobalScopes()
                ->where('admission_no', 'like', $prefix.'%')
                ->lockForUpdate()
                ->pluck('admission_no');

            $maxNumber = $numbers->map(function ($number) use ($prefix) {
                $suffix = substr((string) $number, strlen($prefix));

                return ctype_digit($suffix) ? (int) $suffix : 0;
            })->max() ?? 0;

            $transferNumbers = DB::table('branch_transfers')
                ->where('new_admission_no', 'like', $prefix.'%')
                ->pluck('new_admission_no');
            $maxTransfer = $transferNumbers->map(function ($number) use ($prefix) {
                $suffix = substr((string) $number, strlen($prefix));

                return ctype_digit($suffix) ? (int) $suffix : 0;
            })->max() ?? 0;

            $maxSeq = max($maxNumber, $maxTransfer);

            return $prefix.str_pad((string) ($maxSeq + 1), 3, '0', STR_PAD_LEFT);
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
