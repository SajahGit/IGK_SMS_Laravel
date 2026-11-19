<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Support\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcademicLifecycleService
{
    public function sync(CarbonImmutable $date): array
    {
        return DB::transaction(function () use ($date): array {
            $date = $date->startOfDay();
            $dateString = $date->toDateString();
            $currentYearStart = $date->month >= 9 ? $date->year : $date->year - 1;

            $this->ensureAcademicYear(2026);
            if ($currentYearStart >= 2027) {
                for ($year = 2027; $year <= $currentYearStart; $year++) {
                    $this->ensureAcademicYear($year);
                }
            }

            $targetYear = AcademicYear::query()
                ->whereDate('start_date', '<=', $dateString)
                ->whereDate('end_date', '>=', $dateString)
                ->orderByDesc('start_date')
                ->first();
            $targetTerm = $targetYear
                ? Term::query()
                    ->where('academic_year_id', $targetYear->id)
                    ->whereDate('start_date', '<=', $dateString)
                    ->whereDate('end_date', '>=', $dateString)
                    ->orderBy('sequence')
                    ->first()
                : null;

            $previousYear = AcademicYear::query()->where('is_current', true)->first();
            $previousTerm = Term::query()->where('is_current', true)->first();
            $yearChanged = $targetYear
                && $targetYear->start_date->year >= 2027
                && $previousYear?->id !== $targetYear->id;
            $termChanged = $targetTerm && $previousTerm?->id !== $targetTerm->id;
            $promotionMap = collect();

            foreach (AcademicYear::query()->get() as $year) {
                $isTarget = $targetYear && $year->id === $targetYear->id;
                $year->forceFill([
                    'is_current' => $isTarget,
                    'status' => $isTarget
                        ? 'active'
                        : ($year->end_date->lt($date) ? 'completed' : 'upcoming'),
                ])->save();
            }

            foreach (Term::query()->get() as $term) {
                $isTarget = $targetTerm && $term->id === $targetTerm->id;
                $term->forceFill([
                    'is_current' => $isTarget,
                    'status' => $isTarget
                        ? 'active'
                        : ($term->end_date->lt($date) ? 'completed' : 'upcoming'),
                ])->save();
            }

            if ($yearChanged && $targetYear) {
                $promotionMap = $this->promoteStudents($targetYear, $previousYear, $date);
            }

            if ($termChanged && $targetTerm) {
                $sourceTerm = $previousTerm ?: Term::query()
                    ->whereDate('end_date', '<', $targetTerm->start_date->toDateString())
                    ->orderByDesc('end_date')
                    ->first();

                if ($sourceTerm) {
                    if (! $yearChanged) {
                        $promotionMap = SchoolClass::withoutGlobalScopes()
                            ->pluck('id')
                            ->mapWithKeys(fn ($id) => [(int) $id => (int) $id]);
                    }

                    $this->carryForward($sourceTerm, $targetTerm, $promotionMap, $date);
                }

                $this->publishMilestones($targetTerm, $date);
                $this->logTransition('term', $sourceTerm, $targetTerm, $date);
            }

            if ($yearChanged && $targetYear) {
                $this->logTransition('academic_year', $previousYear, $targetYear, $date);
            }

            return [
                'academic_year' => $targetYear?->name,
                'term' => $targetTerm?->name,
                'year_changed' => (bool) $yearChanged,
                'term_changed' => (bool) $termChanged,
            ];
        });
    }

    private function ensureAcademicYear(int $startYear): AcademicYear
    {
        $yearName = $startYear.'-'.($startYear + 1);
        $yearDates = match ($startYear) {
            2026 => ['2026-09-01', '2027-07-16'],
            2027 => ['2027-09-01', '2028-07-16'],
            default => ["{$startYear}-09-01", ($startYear + 1).'-07-14'],
        };

        $year = AcademicYear::query()->updateOrCreate(
            ['name' => $yearName],
            ['start_date' => $yearDates[0], 'end_date' => $yearDates[1]]
        );

        $termDates = match ($startYear) {
            2026 => [
                ['2026-09-01', '2026-12-18'],
                ['2027-01-18', '2027-04-09'],
                ['2027-04-26', '2027-07-16'],
            ],
            2027 => [
                ['2027-09-01', '2027-12-17'],
                ['2028-01-17', '2028-04-07'],
                ['2028-04-24', '2028-07-14'],
            ],
            default => [
                ["{$startYear}-09-01", ($startYear).'-12-17'],
                [($startYear + 1).'-01-17', ($startYear + 1).'-04-07'],
                [($startYear + 1).'-04-24', ($startYear + 1).'-07-14'],
            ],
        };

        foreach ($termDates as $index => [$startDate, $endDate]) {
            $sequence = $index + 1;
            Term::query()->updateOrCreate(
                ['academic_year_id' => $year->id, 'sequence' => $sequence],
                [
                    'name' => "Term {$sequence}",
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]
            );
        }

        return $year;
    }

    /**
     * @return Collection<int, int> source class ID to promoted class ID
     */
    private function promoteStudents(AcademicYear $year, ?AcademicYear $previousYear, CarbonImmutable $date): Collection
    {
        DB::table('classes')->update(['academic_year_id' => $year->id, 'updated_at' => now()]);

        $classes = SchoolClass::withoutGlobalScopes()->orderBy('id')->get();
        $activeStudentsByClass = Student::withoutGlobalScopes()
            ->where('status', 'active')
            ->get()
            ->groupBy('class_id');
        $promotionMap = collect();
        $promotedCount = 0;
        $graduatedCount = 0;
        $skippedCount = 0;

        foreach ($classes as $class) {
            $nextLevel = $this->nextLevel($class->name);
            if ($nextLevel === 'graduated') {
                foreach ($activeStudentsByClass->get($class->id, collect()) as $student) {
                    $student->forceFill(['status' => 'graduated'])->save();
                    $graduatedCount++;
                }

                continue;
            }

            if ($nextLevel === null || ! $class->program_id) {
                continue;
            }

            $nextClass = $classes->first(fn (SchoolClass $candidate) => (
                $candidate->id !== $class->id
                && $candidate->name === $nextLevel
                && $candidate->section === $class->section
                && (int) $candidate->program_id === (int) $class->program_id
                && $candidate->branch_id === $class->branch_id
            ));

            if (! $nextClass) {
                $skippedCount++;
                Log::warning('Academic lifecycle skipped student promotion because no matching class exists.', [
                    'source_class_id' => $class->id,
                    'source_class' => $class->full_name,
                    'target_level' => $nextLevel,
                    'program_id' => $class->program_id,
                    'branch_id' => $class->branch_id,
                    'academic_year_id' => $year->id,
                ]);

                continue;
            }

            $promotionMap->put($class->id, $nextClass->id);
        }

        foreach ($promotionMap as $sourceClassId => $targetClassId) {
            $targetClass = $classes->firstWhere('id', $targetClassId);
            foreach ($activeStudentsByClass->get($sourceClassId, collect()) as $student) {
                $student->forceFill([
                    'class_id' => $targetClass->id,
                    'program_id' => $targetClass->program_id,
                    'academic_year_id' => $year->id,
                    'branch_id' => $targetClass->branch_id,
                ])->save();
                $promotedCount++;
            }
        }

        AuditLogger::log('academic_year_grade_promotion', $year, null, [
            'previous_academic_year_id' => $previousYear?->id,
            'academic_year_id' => $year->id,
            'effective_date' => $date->toDateString(),
            'promoted_students' => $promotedCount,
            'graduated_students' => $graduatedCount,
            'classes_without_targets' => $skippedCount,
        ]);

        return $promotionMap;
    }

    private function nextLevel(string $name): ?string
    {
        if (in_array($name, ['Year 13', 'DP Year 2', 'Grade 12'], true)) {
            return 'graduated';
        }

        if (in_array($name, ['Nursery', 'Pre-K', 'Reception'], true)) {
            return [
                'Nursery' => 'Pre-K',
                'Pre-K' => 'Reception',
                'Reception' => 'Grade 1',
            ][$name];
        }

        if (preg_match('/^(Year|Grade|PYP|DP Year)\\s+(\\d+)$/', $name, $matches)) {
            return $matches[1].' '.((int) $matches[2] + 1);
        }

        return null;
    }

    /**
     * @param  Collection<int, int>  $classMap
     */
    private function carryForward(Term $sourceTerm, Term $targetTerm, Collection $classMap, CarbonImmutable $date): void
    {
        $offerings = ClassSubject::query()
            ->where('term_id', $sourceTerm->id)
            ->where(function ($query): void {
                $query->whereNull('teacher_id')
                    ->orWhereHas('teacher', fn ($teacher) => $teacher->where('status', 'active'));
            })
            ->with('timetableSlots')
            ->orderBy('id')
            ->get();

        foreach ($offerings as $offering) {
            $targetClassId = $classMap->get($offering->class_id);
            if (! $targetClassId) {
                continue;
            }

            $targetOffering = ClassSubject::query()->firstOrCreate(
                [
                    'class_id' => $targetClassId,
                    'subject_id' => $offering->subject_id,
                    'term_id' => $targetTerm->id,
                ],
                ['teacher_id' => $offering->teacher_id]
            );

            $this->carryForwardEnrollments($offering, $targetOffering, $targetClassId);
            $this->carryForwardTimetable($offering, $targetOffering, $targetTerm);
            $this->seedScheduledAssessment($targetOffering, $targetTerm);
        }
    }

    private function carryForwardEnrollments(ClassSubject $source, ClassSubject $target, int $targetClassId): void
    {
        $studentIds = DB::table('student_subject_enrollments')
            ->join('students', 'students.id', '=', 'student_subject_enrollments.student_id')
            ->where('student_subject_enrollments.class_subject_id', $source->id)
            ->where('student_subject_enrollments.status', 'enrolled')
            ->where('students.status', 'active')
            ->where('students.class_id', $targetClassId)
            ->pluck('students.id');

        foreach ($studentIds as $studentId) {
            DB::table('student_subject_enrollments')->insertOrIgnore([
                'student_id' => $studentId,
                'class_subject_id' => $target->id,
                'status' => 'enrolled',
                'enrolled_at' => now(),
                'dropped_at' => null,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function carryForwardTimetable(ClassSubject $source, ClassSubject $target, Term $targetTerm): void
    {
        foreach ($source->timetableSlots as $slot) {
            if ($this->hasTermClash($target, $targetTerm, $slot)) {
                Log::warning('Academic lifecycle skipped a timetable slot because it conflicts in the target term.', [
                    'source_slot_id' => $slot->id,
                    'target_class_subject_id' => $target->id,
                    'target_term_id' => $targetTerm->id,
                ]);

                continue;
            }

            TimetableSlot::query()->firstOrCreate(
                [
                    'class_subject_id' => $target->id,
                    'day_of_week' => $slot->day_of_week,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                ],
                ['room' => $slot->room]
            );
        }
    }

    private function hasTermClash(ClassSubject $target, Term $term, TimetableSlot $slot): bool
    {
        $branchId = DB::table('classes')->where('id', $target->class_id)->value('branch_id');
        $query = TimetableSlot::query()
            ->join('class_subject as existing_subject', 'existing_subject.id', '=', 'timetable_slots.class_subject_id')
            ->join('classes as existing_class', 'existing_class.id', '=', 'existing_subject.class_id')
            ->where('existing_subject.term_id', $term->id)
            ->where('timetable_slots.class_subject_id', '!=', $target->id)
            ->where('timetable_slots.day_of_week', $slot->day_of_week)
            ->where('timetable_slots.start_time', '<', $slot->end_time)
            ->where('timetable_slots.end_time', '>', $slot->start_time)
            ->where(function ($conflict) use ($branchId, $target, $slot): void {
                $conflict->where('existing_subject.class_id', $target->class_id);
                if ($target->teacher_id) {
                    $conflict->orWhere('existing_subject.teacher_id', $target->teacher_id);
                }
                $conflict->orWhere(function ($roomConflict) use ($branchId, $slot): void {
                    $roomConflict->where('timetable_slots.room', $slot->room);
                    if ($branchId === null) {
                        $roomConflict->whereNull('existing_class.branch_id');
                    } else {
                        $roomConflict->where('existing_class.branch_id', $branchId);
                    }
                });
            });

        return $query->exists();
    }

    private function seedScheduledAssessment(ClassSubject $offering, Term $term): void
    {
        Exam::query()->firstOrCreate(
            [
                'name' => $term->name.' Scheduled Assessment',
                'class_id' => $offering->class_id,
                'subject_id' => $offering->subject_id,
                'term_id' => $term->id,
            ],
            [
                'type' => 'quiz',
                'exam_date' => $term->start_date,
                'max_marks' => 100,
                'weight' => 100,
                'is_published' => false,
                'status' => 'scheduled',
            ]
        );
    }

    private function publishMilestones(Term $term, CarbonImmutable $date): void
    {
        $academicYear = $term->academicYear;
        if (! $academicYear) {
            return;
        }

        $milestones = match ($term->sequence) {
            2 => [[
                'title' => 'Spring Break',
                'start' => ($academicYear->start_date->year + 1).'-03-29',
                'end' => ($academicYear->start_date->year + 1).'-04-09',
            ]],
            3 => [
                [
                    'title' => 'Board Exams',
                    'start' => ($academicYear->start_date->year + 1).'-05-10',
                    'end' => ($academicYear->start_date->year + 1).'-05-28',
                ],
                [
                    'title' => 'Annual Final Exams',
                    'start' => ($academicYear->start_date->year + 1).'-06-28',
                    'end' => ($academicYear->start_date->year + 1).'-07-09',
                ],
            ],
            default => [],
        };

        foreach ($milestones as $milestone) {
            $title = "{$academicYear->name} {$milestone['title']}";
            Announcement::query()->firstOrCreate(
                ['title' => $title, 'program_id' => null, 'class_id' => null],
                [
                    'body' => "{$milestone['title']} runs from {$milestone['start']} to {$milestone['end']}.",
                    'target_role' => 'all',
                    'published_at' => $date,
                    'expires_at' => CarbonImmutable::parse($milestone['end'])->endOfDay(),
                ]
            );
        }
    }

    private function logTransition(string $type, AcademicYear|Term|null $previous, AcademicYear|Term $target, CarbonImmutable $date): void
    {
        AuditLogger::log('academic_lifecycle_'.$type.'_transition', $target, [
            'previous_id' => $previous?->id,
            'previous_name' => $previous?->name,
        ], [
            'target_id' => $target->id,
            'target_name' => $target->name,
            'effective_date' => $date->toDateString(),
        ]);
    }
}
