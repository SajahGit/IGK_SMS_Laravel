<?php

namespace App\Services;

use App\Models\Result;
use App\Models\GradeScale;
use Illuminate\Support\Collection;

/**
 * Canonical grading engine.
 *
 * Grade boundaries and points are loaded from the institution's default grade scale.
 * GPA is rounded half-up to two decimals; excused papers do not count.
 */
class GradingService
{
    public const GRADE_A = 85;

    public const GRADE_B = 75;

    public const GRADE_C = 65;

    public const GRADE_S = 50;

    /** Percentage obtained for one paper. */
    public static function percentage($marks, int $maxMarks): float
    {
        return $maxMarks > 0 && $marks !== null
            ? round((float) $marks / (float) $maxMarks * 100.0, 2, PHP_ROUND_HALF_UP)
            : 0.0;
    }

    /** Letter grade for a percentage. */
    public static function grade(float $percentage, ?GradeScale $scale = null): string
    {
        return GradeScaleService::grade($percentage, $scale);
    }

    /** Grade points for a percentage. */
    public static function gradePoint(float $percentage, ?GradeScale $scale = null): float
    {
        return GradeScaleService::gradePoint($percentage, $scale);
    }

    public static function isPassing(float $percentage, ?GradeScale $scale = null): bool
    {
        return GradeScaleService::isPassing($percentage, $scale);
    }

    /**
     * Weighted subject percentage from the student's exam papers.
     *
     * @param  Collection<int, Result>  $results
     */
    public static function calculateSubjectPercentage(Collection $results): ?float
    {
        $weightedTotal = 0.0;
        $totalWeight = 0.0;

        foreach ($results as $result) {
            if ($result->is_excused) {
                continue;
            }

            if (! $result->is_absent && $result->marks === null) {
                continue;
            }

            $exam = $result->exam;
            $weight = (float) ($exam->weight ?? 100);
            if ($weight <= 0) {
                continue;
            }

            $percentage = $result->is_absent
                ? 0.0
                : ((float) $result->marks / max(1, (int) ($exam->max_marks ?? 100))) * 100;

            $weightedTotal += $percentage * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0
            ? round($weightedTotal / $totalWeight, 2, PHP_ROUND_HALF_UP)
            : null;
    }

    /**
     * Term GPA from weighted subject results (each loaded with exam.subject).
     *
     * @param  Collection<int, Result>  $results
     */
    public static function calculateTermGpa(Collection $results, ?GradeScale $scale = null): ?float
    {
        $totalQualityPoints = 0.0;
        $totalCreditHours = 0;

        foreach ($results->groupBy(fn (Result $result) => $result->exam->subject_id) as $subjectResults) {
            $percentage = self::calculateSubjectPercentage($subjectResults);
            if ($percentage === null) {
                continue;
            }

            $creditHours = (int) ($subjectResults->first()->exam->subject->credit_hours ?? 3);
            $gradePoint = self::gradePoint($percentage, $scale);

            $totalQualityPoints += ($gradePoint * $creditHours);
            $totalCreditHours += $creditHours;
        }

        if ($totalCreditHours === 0) {
            return null;
        }

        return round($totalQualityPoints / $totalCreditHours, 2);
    }
}
