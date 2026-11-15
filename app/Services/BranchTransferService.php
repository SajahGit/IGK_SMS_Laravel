<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchTransfer;
use App\Models\Employee;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\AdmissionNumberGenerator;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchTransferService
{
    /**
     * Transfer a student to another branch, generating destination admission number sequence safely.
     *
     * @throws ValidationException
     */
    public static function transferStudent(
        Student $student,
        int $toBranchId,
        ?int $toClassId,
        string $transferDate,
        ?string $reason,
        int $actorId
    ): Student {
        return DB::transaction(function () use ($student, $toBranchId, $toClassId, $transferDate, $reason, $actorId) {
            /** @var Student $lockedStudent */
            $lockedStudent = Student::withoutGlobalScopes()->lockForUpdate()->findOrFail($student->id);

            $destinationBranch = Branch::withoutGlobalScopes()->where('is_active', true)->lockForUpdate()->findOrFail($toBranchId);

            if ((int) $lockedStudent->branch_id === (int) $toBranchId) {
                throw ValidationException::withMessages([
                    'to_branch_id' => 'The destination branch must be different from the student\'s current branch.',
                ]);
            }

            if ($toClassId !== null) {
                $targetClass = SchoolClass::withoutGlobalScopes()->findOrFail($toClassId);
                if ((int) $targetClass->branch_id !== (int) $toBranchId) {
                    throw ValidationException::withMessages([
                        'class_id' => 'The selected class does not belong to the destination branch.',
                    ]);
                }
                $currentEnrollment = Student::withoutGlobalScopes()->where('class_id', $targetClass->id)->count();
                if ($currentEnrollment >= (int) $targetClass->capacity) {
                    throw ValidationException::withMessages([
                        'class_id' => "The selected class {$targetClass->full_name} has reached maximum capacity ({$targetClass->capacity}).",
                    ]);
                }
            }

            // Generate destination branch admission number sequence
            $previousAdmissionNo = $lockedStudent->admission_no;
            $newAdmissionNo = AdmissionNumberGenerator::nextForBranch(
                $toBranchId,
                $lockedStudent->program_id ? (int) $lockedStudent->program_id : null,
                $lockedStudent->academic_year_id ? (int) $lockedStudent->academic_year_id : null
            );

            // Double check uniqueness across database
            if (Student::withoutGlobalScopes()->where('admission_no', $newAdmissionNo)->exists()) {
                throw ValidationException::withMessages([
                    'to_branch_id' => "Generated admission number {$newAdmissionNo} conflicts with an existing record. Please retry.",
                ]);
            }

            $fromBranchId = $lockedStudent->branch_id;

            // Record Transfer History
            $transferRecord = BranchTransfer::create([
                'person_id' => $lockedStudent->id,
                'person_category' => 'student',
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'previous_admission_no' => $previousAdmissionNo,
                'new_admission_no' => $newAdmissionNo,
                'transfer_date' => $transferDate,
                'reason' => $reason,
                'transferred_by' => $actorId,
            ]);

            // Update student
            $before = $lockedStudent->toArray();
            $updateData = [
                'branch_id' => $toBranchId,
                'admission_no' => $newAdmissionNo,
            ];
            if ($toClassId !== null) {
                $updateData['class_id'] = $toClassId;
            }
            $lockedStudent->update($updateData);

            // Synchronise linked user account branch
            if ($lockedStudent->user_id) {
                DB::table('users')->where('id', $lockedStudent->user_id)->update(['branch_id' => $toBranchId]);
            }

            AuditLogger::log('student_branch_transferred', $lockedStudent, $before, [
                'transfer_id' => $transferRecord->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'previous_admission_no' => $previousAdmissionNo,
                'new_admission_no' => $newAdmissionNo,
                'reason' => $reason,
            ]);

            return $lockedStudent->fresh(['branch', 'class', 'user']);
        });
    }

    /**
     * Transfer an employee to another branch.
     *
     * @throws ValidationException
     */
    public static function transferEmployee(
        Employee $employee,
        int $toBranchId,
        string $transferDate,
        ?string $reason,
        int $actorId
    ): Employee {
        return DB::transaction(function () use ($employee, $toBranchId, $transferDate, $reason, $actorId) {
            /** @var Employee $lockedEmployee */
            $lockedEmployee = Employee::withoutGlobalScopes()->lockForUpdate()->findOrFail($employee->id);

            Branch::withoutGlobalScopes()->where('is_active', true)->lockForUpdate()->findOrFail($toBranchId);

            if ((int) $lockedEmployee->branch_id === (int) $toBranchId) {
                throw ValidationException::withMessages([
                    'to_branch_id' => 'The destination branch must be different from the employee\'s current branch.',
                ]);
            }

            $fromBranchId = $lockedEmployee->branch_id;

            // Record Transfer History
            $transferRecord = BranchTransfer::create([
                'person_id' => $lockedEmployee->id,
                'person_category' => 'employee',
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'previous_admission_no' => null,
                'new_admission_no' => null,
                'transfer_date' => $transferDate,
                'reason' => $reason,
                'transferred_by' => $actorId,
            ]);

            $before = $lockedEmployee->toArray();
            $lockedEmployee->update(['branch_id' => $toBranchId]);

            // Synchronise linked user account branch
            if ($lockedEmployee->user_id) {
                DB::table('users')->where('id', $lockedEmployee->user_id)->update(['branch_id' => $toBranchId]);
            }

            AuditLogger::log('employee_branch_transferred', $lockedEmployee, $before, [
                'transfer_id' => $transferRecord->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'reason' => $reason,
            ]);

            return $lockedEmployee->fresh(['branch', 'user', 'department']);
        });
    }

    /**
     * Transfer a teacher to another branch.
     *
     * @throws ValidationException
     */
    public static function transferTeacher(
        \App\Models\Teacher $teacher,
        int $toBranchId,
        string $transferDate,
        ?string $reason,
        int $actorId
    ): \App\Models\Teacher {
        return DB::transaction(function () use ($teacher, $toBranchId, $transferDate, $reason, $actorId) {
            /** @var \App\Models\Teacher $lockedTeacher */
            $lockedTeacher = \App\Models\Teacher::withoutGlobalScopes()->lockForUpdate()->findOrFail($teacher->id);

            Branch::withoutGlobalScopes()->where('is_active', true)->lockForUpdate()->findOrFail($toBranchId);

            if ((int) $lockedTeacher->branch_id === (int) $toBranchId) {
                throw ValidationException::withMessages([
                    'to_branch_id' => 'The destination branch must be different from the teacher\'s current branch.',
                ]);
            }

            $fromBranchId = $lockedTeacher->branch_id;

            // Record Transfer History
            $transferRecord = BranchTransfer::create([
                'person_id' => $lockedTeacher->id,
                'person_category' => 'teacher',
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'previous_admission_no' => null,
                'new_admission_no' => null,
                'transfer_date' => $transferDate,
                'reason' => $reason,
                'transferred_by' => $actorId,
            ]);

            $before = $lockedTeacher->toArray();
            $lockedTeacher->update(['branch_id' => $toBranchId]);

            // Synchronise linked user account branch
            if ($lockedTeacher->user_id) {
                DB::table('users')->where('id', $lockedTeacher->user_id)->update(['branch_id' => $toBranchId]);
            }

            AuditLogger::log('teacher_branch_transferred', $lockedTeacher, $before, [
                'transfer_id' => $transferRecord->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'reason' => $reason,
            ]);

            return $lockedTeacher->fresh(['branch', 'user', 'department']);
        });
    }
}
