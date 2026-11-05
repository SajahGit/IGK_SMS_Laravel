<?php

namespace App\Services;

use App\Models\GradeScale;
use App\Models\GradeScaleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradeScaleService
{
    private static ?\WeakMap $defaultItems = null;

    private const DEFAULT_ITEMS = [
        ['min_percent' => 85, 'max_percent' => 100, 'grade' => 'A', 'grade_point' => 4, 'is_pass' => true],
        ['min_percent' => 75, 'max_percent' => 84.99, 'grade' => 'B', 'grade_point' => 3, 'is_pass' => true],
        ['min_percent' => 65, 'max_percent' => 74.99, 'grade' => 'C', 'grade_point' => 2, 'is_pass' => true],
        ['min_percent' => 50, 'max_percent' => 64.99, 'grade' => 'S', 'grade_point' => 1, 'is_pass' => true],
        ['min_percent' => 0, 'max_percent' => 49.99, 'grade' => 'F', 'grade_point' => 0, 'is_pass' => false],
    ];

    public static function grade(float $percentage): string
    {
        return (string) self::itemFor($percentage)->grade;
    }

    public static function gradePoint(float $percentage): float
    {
        return (float) self::itemFor($percentage)->grade_point;
    }

    public static function isPassing(float $percentage): bool
    {
        return (bool) self::itemFor($percentage)->is_pass;
    }

    public static function defaultItems(): Collection
    {
        self::$defaultItems ??= new \WeakMap;
        $application = app();
        if (! isset(self::$defaultItems[$application])) {
            $scale = GradeScale::with('items')->where('is_default', true)->first();
            self::$defaultItems[$application] = ($scale?->items ?? collect(self::DEFAULT_ITEMS)->map(
                fn (array $item) => new GradeScaleItem($item)
            ))->sortByDesc('min_percent')->values();
        }

        return self::$defaultItems[$application];
    }

    public static function clearCache(): void
    {
        self::$defaultItems = new \WeakMap;
    }

    public static function validateRanges(array $items): void
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'A grade scale must contain at least one grade range.',
            ]);
        }

        usort($items, fn (array $a, array $b) => (float) $a['min_percent'] <=> (float) $b['min_percent']);
        $expectedMinimum = 0.0;

        foreach ($items as $item) {
            $minimum = round((float) $item['min_percent'], 2);
            $maximum = round((float) $item['max_percent'], 2);

            if ($minimum !== $expectedMinimum || $minimum > $maximum || $maximum > 100) {
                throw ValidationException::withMessages([
                    'items' => 'Grade ranges must be contiguous, non-overlapping, and cover 0 through 100.',
                ]);
            }

            $expectedMinimum = round($maximum + 0.01, 2);
        }

        if ($expectedMinimum !== 100.01) {
            throw ValidationException::withMessages([
                'items' => 'Grade ranges must cover the complete 0 through 100 percent interval.',
            ]);
        }
    }

    public static function updateDefault(string $name, array $items): GradeScale
    {
        self::validateRanges($items);

        return DB::transaction(function () use ($name, $items) {
            $scale = GradeScale::firstOrCreate(
                ['is_default' => true],
                ['name' => $name]
            );
            GradeScale::where('is_default', true)->whereKeyNot($scale->id)->update(['is_default' => false]);
            $scale->update(['name' => $name]);
            $scale->items()->delete();

            foreach ($items as $item) {
                $scale->items()->create($item);
            }

            self::clearCache();

            return $scale->load('items');
        });
    }

    private static function itemFor(float $percentage): GradeScaleItem
    {
        $item = self::defaultItems()->first(
            fn (GradeScaleItem $item) => $percentage >= (float) $item->min_percent
                && $percentage <= (float) $item->max_percent
        );

        if (! $item) {
            throw new \LogicException('The configured grade scale does not cover this percentage.');
        }

        return $item;
    }
}
