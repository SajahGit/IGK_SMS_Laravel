<?php

namespace App\Services;

use App\Events\TermResultsPublished;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Notifications\TermResultsPublished as TermResultsPublishedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TermResultService
{
    public function enrolledStudentCount(Term $term, int $classId): int
    {
        $offerings = ClassSubject::query()
            ->where('class_id', $classId)
            ->where(fn ($query) => $query->where('term_id', $term->id)->orWhereNull('term_id'))
            ->get()
            ->groupBy('subject_id')
            ->map(fn ($matches) => $matches->firstWhere('term_id', $term->id) ?? $matches->first());

        return DB::table('student_subject_enrollments')
            ->join('students', 'students.id', '=', 'student_subject_enrollments.student_id')
            ->whereIn('student_subject_enrollments.class_subject_id', $offerings->pluck('id'))
            ->where('student_subject_enrollments.status', 'enrolled')
            ->where('students.status', 'active')
            ->distinct()
            ->count('students.id');
    }

    public function generate(Term $term, ?int $classId = null): array
    {
        $classes = SchoolClass::query()
            ->when($classId, fn ($query) => $query->whereKey($classId))
            ->whereHas('classSubjects', fn ($query) => $query
                ->where(function ($offerings) use ($term) {
                    $offerings->where('term_id', $term->id)->orWhereNull('term_id');
                }))
            ->get();

        if ($classes->isEmpty()) {
            throw ValidationException::withMessages([
                'class_id' => 'No class subject offerings are configured for this term and class.',
            ]);
        }

        $locked = TermResult::query()
            ->where('term_id', $term->id)
            ->whereIn('class_id', $classes->modelKeys())
            ->whereIn('publication_status', ['pending_approval', 'published'])
            ->exists();
        if ($locked) {
            throw ValidationException::withMessages([
                'term' => 'Submitted or published term results must be unpublished before recalculation.',
            ]);
        }

        $missingPairs = [];
        $generatedCount = 0;
        $now = now();

        DB::transaction(function () use ($classes, $term, $now, &$missingPairs, &$generatedCount) {
            foreach ($classes as $class) {
                $offerings = ClassSubject::query()
                    ->with([
                        'subject',
                        'students' => fn ($query) => $query
                            ->where('students.status', 'active')
                            ->wherePivot('status', 'enrolled')
                            ->with('user'),
                    ])
                    ->where('class_id', $class->id)
                    ->where(fn ($query) => $query->where('term_id', $term->id)->orWhereNull('term_id'))
                    ->get()
                    ->groupBy('subject_id')
                    ->map(fn ($matches) => $matches->firstWhere('term_id', $term->id) ?? $matches->first());

                $pairKeys = [];
                $studentIds = [];

                foreach ($offerings as $offering) {
                    $exams = Exam::query()
                        ->where('term_id', $term->id)
                        ->where('class_id', $class->id)
                        ->where('subject_id', $offering->subject_id)
                        ->orderBy('exam_date')
                        ->orderBy('id')
                        ->get();

                    foreach ($offering->students as $student) {
                        $studentIds[] = $student->id;
                        $pairKey = $student->id.':'.$offering->subject_id;
                        $pairKeys[] = $pairKey;
                        $examResults = Result::query()
                            ->with('exam.subject')
                            ->where('student_id', $student->id)
                            ->whereIn('exam_id', $exams->modelKeys())
                            ->get()
                            ->keyBy('exam_id');

                        $missingExams = [];
                        foreach ($exams as $exam) {
                            $result = $examResults->get($exam->id);
                            if (! $exam->is_published) {
                                $missingExams[] = $exam->name.' (not published)';
                            } elseif (! $result || (! $result->is_absent && $result->marks === null)) {
                                $missingExams[] = $exam->name.' (marks missing)';
                            }
                        }
                        if ($exams->isEmpty()) {
                            $missingExams[] = 'No exam is configured for this subject in the selected term';
                        }

                        $percentage = null;
                        $status = 'incomplete';
                        $grade = null;
                        $gradePoint = null;
                        if ($missingExams === []) {
                            $percentage = GradingService::calculateSubjectPercentage($examResults->values());
                            if ($percentage === null) {
                                $status = 'excused';
                            } else {
                                $status = 'graded';
                                $grade = GradingService::grade($percentage);
                                $gradePoint = GradingService::gradePoint($percentage);
                            }
                        } else {
                            $missingPairs[] = [
                                'student' => $student->user?->name ?? $student->admission_no,
                                'admission_no' => $student->admission_no,
                                'subject' => $offering->subject->name,
                                'missing_exams' => $missingExams,
                            ];
                        }

                        SubjectResult::updateOrCreate(
                            [
                                'term_id' => $term->id,
                                'class_id' => $class->id,
                                'student_id' => $student->id,
                                'subject_id' => $offering->subject_id,
                            ],
                            [
                                'percentage' => $percentage,
                                'grade' => $grade,
                                'grade_point' => $gradePoint,
                                'credit_hours' => $status === 'excused' ? null : $offering->subject->credit_hours,
                                'result_status' => $status,
                                'generated_at' => $now,
                            ]
                        );
                        $generatedCount++;
                    }
                }

                $studentIds = array_values(array_unique($studentIds));
                $existingSubjectResults = SubjectResult::query()
                    ->where('term_id', $term->id)
                    ->where('class_id', $class->id)
                    ->get();
                foreach ($existingSubjectResults as $existing) {
                    if (! in_array($existing->student_id.':'.$existing->subject_id, $pairKeys, true)) {
                        $existing->delete();
                    }
                }

                foreach ($studentIds as $studentId) {
                    $subjects = SubjectResult::query()
                        ->where('term_id', $term->id)
                        ->where('class_id', $class->id)
                        ->where('student_id', $studentId)
                        ->get();
                    $incomplete = $subjects->contains('result_status', 'incomplete');
                    $graded = $subjects->where('result_status', 'graded');
                    $creditHours = $graded->sum('credit_hours');
                    $gpa = $creditHours > 0
                        ? round($graded->sum(fn (SubjectResult $subject) => (float) $subject->grade_point * $subject->credit_hours) / $creditHours, 2, PHP_ROUND_HALF_UP)
                        : null;
                    $resultStatus = $incomplete
                        ? 'incomplete'
                        : ($graded->isEmpty()
                            ? 'excused'
                            : ($graded->every(fn (SubjectResult $subject) => GradingService::isPassing((float) $subject->percentage) && $subject->result_status === 'graded')
                                ? 'pass'
                                : 'fail'));

                    TermResult::updateOrCreate(
                        ['term_id' => $term->id, 'class_id' => $class->id, 'student_id' => $studentId],
                        [
                            'gpa' => $gpa,
                            'result_status' => $resultStatus,
                            'publication_status' => 'draft',
                            'submitted_by' => null,
                            'approved_by' => null,
                            'published_by' => null,
                            'unpublish_reason' => null,
                            'generated_at' => $now,
                            'submitted_at' => null,
                            'approved_at' => null,
                            'published_at' => null,
                        ]
                    );
                }

                TermResult::query()
                    ->where('term_id', $term->id)
                    ->where('class_id', $class->id)
                    ->whereNotIn('student_id', $studentIds ?: [0])
                    ->delete();
            }
        });

        return [
            'classes' => $classes->count(),
            'generated_subject_results' => $generatedCount,
            'missing_pairs' => $missingPairs,
        ];
    }

    public function submitForApproval(Term $term, int $classId, User $actor): int
    {
        $results = TermResult::query()
            ->where('term_id', $term->id)
            ->where('class_id', $classId)
            ->where('publication_status', 'draft')
            ->with('student.user')
            ->get();

        if ($results->isEmpty()) {
            throw ValidationException::withMessages([
                'results' => 'Generate term results before submitting them for approval.',
            ]);
        }

        $incomplete = $results->where('result_status', 'incomplete');
        if ($incomplete->isNotEmpty()) {
            $details = $incomplete->flatMap(function (TermResult $result) {
                return SubjectResult::query()
                    ->with('subject')
                    ->where('term_id', $result->term_id)
                    ->where('class_id', $result->class_id)
                    ->where('student_id', $result->student_id)
                    ->where('result_status', 'incomplete')
                    ->get()
                    ->map(fn (SubjectResult $subject) => ($result->student->user?->name ?? $result->student->admission_no)
                        .' / '.$subject->subject->name);
            })->unique()->values();

            throw ValidationException::withMessages([
                'results' => 'Missing marks or unpublished exams for: '.$details->implode('; '),
            ]);
        }

        return DB::transaction(function () use ($results, $actor) {
            foreach ($results as $result) {
                $result->update([
                    'publication_status' => 'pending_approval',
                    'submitted_by' => $actor->id,
                    'submitted_at' => now(),
                ]);
            }

            return $results->count();
        });
    }

    public function publishClass(Term $term, int $classId, User $actor): int
    {
        $results = TermResult::query()
            ->where('term_id', $term->id)
            ->where('class_id', $classId)
            ->where('publication_status', 'pending_approval')
            ->get();

        if ($results->isEmpty()) {
            throw ValidationException::withMessages([
                'results' => 'Submit complete term results for approval before publishing.',
            ]);
        }

        if ($results->contains('result_status', 'incomplete')) {
            throw ValidationException::withMessages([
                'results' => 'Incomplete term results cannot be published.',
            ]);
        }

        $count = DB::transaction(function () use ($results, $actor) {
            foreach ($results as $result) {
                $result->update([
                    'publication_status' => 'published',
                    'approved_by' => $actor->id,
                    'published_by' => $actor->id,
                    'approved_at' => now(),
                    'published_at' => now(),
                    'unpublish_reason' => null,
                ]);
            }

            return $results->count();
        });

        $schoolClass = SchoolClass::findOrFail($classId);
        $publishedResults = TermResult::with(['student.user', 'student.guardians.user'])
            ->where('term_id', $term->id)
            ->where('class_id', $classId)
            ->where('publication_status', 'published')
            ->get();
        $recipients = collect();
        foreach ($publishedResults as $result) {
            if ($result->student->user) {
                $recipients->push($result->student->user);
            }
            $recipients->push(...$result->student->guardians->pluck('user')->filter());
        }

        TermResultsPublished::dispatch($term, $classId, $actor->id);
        foreach ($recipients->unique('id') as $recipient) {
            $recipient->notify(new TermResultsPublishedNotification($term, $schoolClass));
        }

        return $count;
    }

    public function unpublishClass(Term $term, int $classId, string $reason): int
    {
        return DB::transaction(function () use ($term, $classId, $reason) {
            $results = TermResult::query()
                ->where('term_id', $term->id)
                ->where('class_id', $classId)
                ->where('publication_status', 'published')
                ->get();

            foreach ($results as $result) {
                $result->update([
                    'publication_status' => 'draft',
                    'approved_by' => null,
                    'published_by' => null,
                    'approved_at' => null,
                    'published_at' => null,
                    'unpublish_reason' => $reason,
                ]);
            }

            return $results->count();
        });
    }
}
