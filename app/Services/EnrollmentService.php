<?php

namespace App\Services;

use App\Exceptions\EnrollmentException;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Support\AdmissionNumberGenerator;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EnrollmentService
{
    /**
     * Enrol a student into a class within a single transaction.
     *
     * Enforces class capacity, generates the admission number when omitted,
     * creates or links the login user and student record, and audits the mutation.
     *
     * @param  array  $data  name, email, password, existing_user_id?, class_id, admission_no?, dob?, phone?, guardian_id?
     *
     * @throws ValidationException
     * @throws EnrollmentException
     */
    public static function enrollStudent(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            $schoolClass = SchoolClass::findOrFail($data['class_id']);

            $currentEnrollment = Student::where('class_id', $schoolClass->id)->count();
            if ($currentEnrollment >= (int) $schoolClass->capacity) {
                throw ValidationException::withMessages([
                    'class_id' => "Class {$schoolClass->full_name} has reached maximum capacity ({$schoolClass->capacity} students).",
                ]);
            }

            $programId = $data['program_id'] ?? $schoolClass->program_id;
            $academicYearId = $data['academic_year_id'] ?? $schoolClass->academic_year_id;
            if (! $programId || ! $academicYearId) {
                throw ValidationException::withMessages([
                    'class_id' => 'The selected class must have a program and academic year before student admission.',
                ]);
            }

            $admissionNo = ! empty($data['admission_no'])
                ? $data['admission_no']
                : AdmissionNumberGenerator::next((int) $programId, (int) $academicYearId);

            if (! preg_match('/^\d{4}-[A-Z]{2,6}-\d{4}$/', $admissionNo)) {
                throw ValidationException::withMessages([
                    'admission_no' => 'Admission numbers must use the YYYY-PROGRAM-#### format.',
                ]);
            }

            $expectedPrefix = AdmissionNumberGenerator::prefix((int) $programId, (int) $academicYearId);
            if (strncmp($admissionNo, $expectedPrefix, strlen($expectedPrefix)) !== 0) {
                throw ValidationException::withMessages([
                    'admission_no' => 'The admission number must match the selected academic year and program.',
                ]);
            }

            if (Student::where('admission_no', $admissionNo)->exists()) {
                throw ValidationException::withMessages([
                    'admission_no' => 'Admission number already exists.',
                ]);
            }

            if (! empty($data['existing_user_id'])) {
                $user = User::whereKey($data['existing_user_id'])->lockForUpdate()->first();

                if (! $user || ! in_array(strtolower($user->role), ['student'], true) || $user->student()->exists()) {
                    throw ValidationException::withMessages([
                        'existing_user_id' => 'Choose an existing student login that is not already linked to a student profile.',
                    ]);
                }

                $user->update(['role' => 'student']);
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => 'student',
                    'is_active' => true,
                    'must_change_password' => true,
                ]);
            }

            if (Role::where('name', 'Student')->where('guard_name', 'web')->exists()) {
                $user->syncRoles(['Student']);
            }

            $student = Student::create([
                'user_id' => $user->id,
                'class_id' => $schoolClass->id,
                'program_id' => $programId,
                'academic_year_id' => $academicYearId,
                'guardian_id' => $data['primary_guardian_id'] ?? $data['guardian_id'] ?? null,
                'admission_no' => $admissionNo,
                'admission_date' => $data['admission_date'] ?? today()->toDateString(),
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'national_id' => $data['national_id'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            self::syncGuardians($student, $data);
            self::syncClassSubjects($student);
            AuditLogger::log('enrolled_student', $student, null, $student->toArray());

            return $student->load(['user', 'class']);
        });
    }

    public static function syncClassSubjects(Student $student): void
    {
        $currentTermId = Term::where('is_current', true)->value('id');
        $classSubjectIds = $student->class->classSubjects()
            ->where(function ($query) use ($currentTermId) {
                $query->whereNull('term_id');
                if ($currentTermId) {
                    $query->orWhere('term_id', $currentTermId);
                }
            })
            ->pluck('class_subject.id');

        $existing = DB::table('student_subject_enrollments')
            ->where('student_id', $student->id)
            ->get(['class_subject_id', 'status'])
            ->keyBy('class_subject_id');
        $now = now();
        $completedIds = $existing
            ->filter(fn ($enrollment) => $enrollment->status === 'enrolled' && ! $classSubjectIds->contains($enrollment->class_subject_id))
            ->pluck('class_subject_id');

        if ($completedIds->isNotEmpty()) {
            DB::table('student_subject_enrollments')
                ->where('student_id', $student->id)
                ->whereIn('class_subject_id', $completedIds)
                ->update(['status' => 'completed', 'updated_at' => $now]);
        }

        $newEnrollments = $classSubjectIds
            ->filter(fn ($id) => ! isset($existing[$id]) || $existing[$id]->status !== 'enrolled')
            ->mapWithKeys(fn ($id) => [
                $id => [
                    'status' => 'enrolled',
                    'enrolled_at' => $now,
                    'dropped_at' => null,
                    'created_by' => auth()->id(),
                ],
            ]);

        if ($newEnrollments->isNotEmpty()) {
            $student->classSubjects()->syncWithoutDetaching($newEnrollments->all());
        }
    }

    public static function syncGuardians(Student $student, array $data): void
    {
        $guardianIds = collect($data['guardian_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->when(
                empty($data['guardian_ids']) && ! empty($data['guardian_id']),
                fn ($ids) => $ids->push((int) $data['guardian_id'])
            )
            ->unique()
            ->values();
        $primaryGuardianId = isset($data['primary_guardian_id'])
            ? (int) $data['primary_guardian_id']
            : (isset($data['guardian_id']) ? (int) $data['guardian_id'] : null);

        if ($primaryGuardianId && ! $guardianIds->contains($primaryGuardianId)) {
            $guardianIds->push($primaryGuardianId);
        }

        if ($guardianIds->isEmpty()) {
            throw ValidationException::withMessages([
                'guardian_ids' => 'A student must have at least one guardian.',
            ]);
        }

        $sync = $guardianIds->mapWithKeys(fn ($id) => [
            $id => ['is_primary' => $id === $primaryGuardianId],
        ])->all();

        $student->guardians()->sync($sync);
        $student->update(['guardian_id' => $primaryGuardianId]);
    }

    /**
     * Drop a student from their class.
     *
     * In this schema a student record is the enrolment, so dropping removes the
     * student record and its login user inside one transaction, with an audit trail.
     */
    public static function dropStudent(Student $student): void
    {
        DB::transaction(function () use ($student) {
            AuditLogger::log('dropped_student', $student, $student->toArray(), null);

            foreach ($student->documents as $doc) {
                Storage::disk('local')->delete($doc->path);
            }

            $student->user?->delete();
            $student->delete();
        });
    }
}
