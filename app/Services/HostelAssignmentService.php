<?php

namespace App\Services;

use App\Models\HostelAssignment;
use App\Models\HostelRoom;
use App\Models\Student;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HostelAssignmentService
{
    public function assign(?HostelAssignment $assignment, array $data): HostelAssignment
    {
        return DB::transaction(function () use ($assignment, $data): HostelAssignment {
            $student = Student::query()->lockForUpdate()->findOrFail($data['student_id']);
            if ($student->status !== 'active') {
                throw ValidationException::withMessages([
                    'student_id' => 'Only active students can receive a hostel assignment.',
                ]);
            }
            $room = HostelRoom::query()->lockForUpdate()->findOrFail($data['hostel_room_id']);
            if (! $room->is_active || ! $room->hostel()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'hostel_room_id' => 'Choose an active room in an active hostel.',
                ]);
            }
            $hostelBranchId = $room->hostel()->value('branch_id');
            if (($hostelBranchId !== null || $student->branch_id !== null)
                && (int) $hostelBranchId !== (int) $student->branch_id) {
                throw ValidationException::withMessages([
                    'hostel_room_id' => 'The student and hostel must belong to the same campus.',
                ]);
            }

            if ($data['bed_number'] > $room->bed_capacity) {
                throw ValidationException::withMessages([
                    'bed_number' => 'The bed number exceeds this room capacity.',
                ]);
            }

            $overlappingBed = $room->assignments()
                ->where('bed_number', $data['bed_number'])
                ->where('status', 'active')
                ->where('id', '!=', $assignment?->id ?? 0)
                ->whereDate('start_date', '<=', $data['end_date'] ?? '9999-12-31')
                ->where(fn ($query) => $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $data['start_date']))
                ->exists();
            if ($overlappingBed) {
                throw ValidationException::withMessages([
                    'bed_number' => 'This bed is already assigned during the selected dates.',
                ]);
            }

            $overlappingStudent = HostelAssignment::query()
                ->where('student_id', $data['student_id'])
                ->where('status', 'active')
                ->where('id', '!=', $assignment?->id ?? 0)
                ->whereDate('start_date', '<=', $data['end_date'] ?? '9999-12-31')
                ->where(fn ($query) => $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $data['start_date']))
                ->exists();
            if ($overlappingStudent) {
                throw ValidationException::withMessages([
                    'student_id' => 'This student already has an overlapping hostel assignment for the academic year.',
                ]);
            }

            $oldValues = $assignment?->only([
                'student_id', 'hostel_room_id', 'academic_year_id', 'bed_number', 'start_date', 'end_date', 'status',
            ]);
            $assignment ??= new HostelAssignment;
            $assignment->fill($data);
            $assignment->status = $data['status'] ?? 'active';
            $assignment->save();
            AuditLogger::log(
                $oldValues ? 'updated' : 'created',
                $assignment,
                $oldValues,
                $assignment->only([
                    'student_id', 'hostel_room_id', 'academic_year_id', 'bed_number', 'start_date', 'end_date', 'status',
                ])
            );

            return $assignment;
        });
    }

    public function end(HostelAssignment $assignment, string $endDate): HostelAssignment
    {
        return DB::transaction(function () use ($assignment, $endDate): HostelAssignment {
            $locked = HostelAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($locked->status !== 'active') {
                throw ValidationException::withMessages(['assignment' => 'Only active assignments can be ended.']);
            }

            $oldValues = $locked->only(['status', 'end_date']);
            $locked->update(['status' => 'inactive', 'end_date' => $endDate]);
            AuditLogger::log('ended', $locked, $oldValues, $locked->only(['status', 'end_date']));

            return $locked;
        });
    }
}
