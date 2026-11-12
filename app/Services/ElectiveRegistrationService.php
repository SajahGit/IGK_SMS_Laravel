<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\Curriculum;
use App\Models\Student;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Support\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ElectiveRegistrationService
{
    public function currentTermFor(Student $student): ?Term
    {
        return Term::query()
            ->where('academic_year_id', $student->academic_year_id)
            ->where('is_current', true)
            ->first();
    }

    /** @return Collection<int, ClassSubject> */
    public function offeringsFor(Student $student): Collection
    {
        $term = $this->currentTermFor($student);
        if (! $term || ! $student->class_id || ! $student->program_id) {
            return collect();
        }

        $electiveSubjectIds = Curriculum::query()
            ->where('program_id', $student->program_id)
            ->where('term_number', $term->sequence)
            ->where('is_elective', true)
            ->pluck('subject_id');

        return ClassSubject::query()
            ->with(['subject', 'teacher.user', 'timetableSlots'])
            ->where('class_id', $student->class_id)
            ->where(fn ($query) => $query->whereNull('term_id')->orWhere('term_id', $term->id))
            ->whereIn('subject_id', $electiveSubjectIds)
            ->orderBy('subject_id')
            ->get();
    }

    public function registrationIsOpen(Student $student): bool
    {
        $term = $this->currentTermFor($student);

        return $term !== null
            && today()->greaterThanOrEqualTo($term->start_date)
            && today()->lessThanOrEqualTo($term->end_date);
    }

    public function register(Student $student, ClassSubject $offering): void
    {
        DB::transaction(function () use ($student, $offering): void {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $offering = ClassSubject::query()->with('schoolClass')->lockForUpdate()->findOrFail($offering->id);
            $term = $this->assertElectiveOpen($student, $offering);

            $enrollment = DB::table('student_subject_enrollments')
                ->where('student_id', $student->id)
                ->where('class_subject_id', $offering->id)
                ->lockForUpdate()
                ->first();
            if ($enrollment?->status === 'enrolled') {
                throw ValidationException::withMessages(['offering' => 'You are already enrolled in this elective.']);
            }

            $enrolledCount = DB::table('student_subject_enrollments')
                ->where('class_subject_id', $offering->id)
                ->where('status', 'enrolled')
                ->count();
            if ($enrolledCount >= (int) $offering->schoolClass->capacity) {
                throw ValidationException::withMessages(['offering' => 'This elective has reached the class capacity.']);
            }

            $this->assertNoTimetableClash($student, $offering);

            $now = now();
            if ($enrollment) {
                DB::table('student_subject_enrollments')
                    ->where('id', $enrollment->id)
                    ->update([
                        'status' => 'enrolled',
                        'enrolled_at' => $now,
                        'dropped_at' => null,
                        'created_by' => auth()->id(),
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('student_subject_enrollments')->insert([
                    'student_id' => $student->id,
                    'class_subject_id' => $offering->id,
                    'status' => 'enrolled',
                    'enrolled_at' => $now,
                    'dropped_at' => null,
                    'created_by' => auth()->id(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            AuditLogger::log('registered_elective', $offering, null, [
                'student_id' => $student->id,
                'subject_id' => $offering->subject_id,
                'term_id' => $term->id,
            ]);
        });
    }

    public function drop(Student $student, ClassSubject $offering): void
    {
        DB::transaction(function () use ($student, $offering): void {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $offering = ClassSubject::query()->findOrFail($offering->id);
            $term = $this->assertElectiveOpen($student, $offering);

            $enrollment = DB::table('student_subject_enrollments')
                ->where('student_id', $student->id)
                ->where('class_subject_id', $offering->id)
                ->where('status', 'enrolled')
                ->lockForUpdate()
                ->first();
            if (! $enrollment) {
                throw ValidationException::withMessages(['offering' => 'You are not enrolled in this elective.']);
            }

            DB::table('student_subject_enrollments')->where('id', $enrollment->id)->update([
                'status' => 'dropped',
                'dropped_at' => now(),
                'updated_at' => now(),
            ]);

            AuditLogger::log('dropped_elective', $offering, ['student_id' => $student->id, 'status' => 'enrolled'], [
                'student_id' => $student->id,
                'status' => 'dropped',
                'term_id' => $term->id,
            ]);
        });
    }

    private function assertElectiveOpen(Student $student, ClassSubject $offering): Term
    {
        $term = $this->currentTermFor($student);
        if (! $term || ! $this->registrationIsOpen($student)) {
            throw ValidationException::withMessages(['offering' => 'Elective registration is closed for the current term.']);
        }
        if ($student->status !== 'active' || $offering->class_id !== $student->class_id
            || ($offering->term_id !== null && (int) $offering->term_id !== (int) $term->id)) {
            throw ValidationException::withMessages(['offering' => 'This elective is not available to your current class and term.']);
        }

        $isElective = Curriculum::query()
            ->where('program_id', $student->program_id)
            ->where('subject_id', $offering->subject_id)
            ->where('term_number', $term->sequence)
            ->where('is_elective', true)
            ->exists();
        if (! $isElective) {
            throw ValidationException::withMessages(['offering' => 'Only curriculum electives can be selected here.']);
        }

        return $term;
    }

    private function assertNoTimetableClash(Student $student, ClassSubject $offering): void
    {
        $enrolledIds = DB::table('student_subject_enrollments')
            ->where('student_id', $student->id)
            ->where('status', 'enrolled')
            ->where('class_subject_id', '!=', $offering->id)
            ->pluck('class_subject_id');
        if ($enrolledIds->isEmpty()) {
            return;
        }

        $existingSlots = TimetableSlot::query()->whereIn('class_subject_id', $enrolledIds)->get();
        $candidateSlots = TimetableSlot::query()->where('class_subject_id', $offering->id)->get();
        foreach ($candidateSlots as $candidate) {
            foreach ($existingSlots as $existing) {
                if ($candidate->day_of_week === $existing->day_of_week
                    && $candidate->start_time < $existing->end_time
                    && $candidate->end_time > $existing->start_time) {
                    throw ValidationException::withMessages(['offering' => 'This elective conflicts with your timetable.']);
                }
            }
        }
    }
}
