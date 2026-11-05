<?php

namespace App\Support;

use App\Models\ClassSubject;
use App\Models\TimetableSlot;
use Illuminate\Validation\ValidationException;

class TimetableClashDetector
{
    /**
     * Verify that a timetable slot does not clash with existing class, teacher, or room bookings.
     *
     * @throws ValidationException
     */
    public static function checkClash(
        int $classSubjectId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        string $room,
        ?int $ignoreSlotId = null
    ): void {
        $classSubject = ClassSubject::with(['schoolClass', 'teacher.user', 'subject'])->findOrFail($classSubjectId);

        // Normalize time strings (e.g. HH:MM:SS or HH:MM)
        $start = substr($startTime, 0, 5).':00';
        $end = substr($endTime, 0, 5).':00';

        if (strcmp($start, $end) >= 0) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        // 1. Room Clash
        $roomClash = TimetableSlot::with(['classSubject.schoolClass', 'classSubject.subject'])
            ->where('day_of_week', $dayOfWeek)
            ->where('room', $room)
            ->when($ignoreSlotId, fn ($q) => $q->where('id', '!=', $ignoreSlotId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->first();

        if ($roomClash) {
            throw ValidationException::withMessages([
                'room' => "Room '{$room}' is already occupied on this day between ".
                    substr($roomClash->start_time, 0, 5).' and '.substr($roomClash->end_time, 0, 5).
                    " by {$roomClash->classSubject->schoolClass->full_name} ({$roomClash->classSubject->subject->name}).",
            ]);
        }

        // 2. Class Clash
        $classClash = TimetableSlot::with(['classSubject.subject'])
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('classSubject', fn ($q) => $q->where('class_id', $classSubject->class_id))
            ->when($ignoreSlotId, fn ($q) => $q->where('id', '!=', $ignoreSlotId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->first();

        if ($classClash) {
            throw ValidationException::withMessages([
                'class_subject_id' => "Class '{$classSubject->schoolClass->full_name}' already has a session scheduled between ".
                    substr($classClash->start_time, 0, 5).' and '.substr($classClash->end_time, 0, 5).
                    " ({$classClash->classSubject->subject->name}).",
            ]);
        }

        // 3. Teacher Clash
        $teacherClash = TimetableSlot::with(['classSubject.schoolClass', 'classSubject.subject'])
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('classSubject', fn ($q) => $q->where('teacher_id', $classSubject->teacher_id))
            ->when($ignoreSlotId, fn ($q) => $q->where('id', '!=', $ignoreSlotId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->first();

        if ($teacherClash) {
            throw ValidationException::withMessages([
                'class_subject_id' => "Teacher '{$classSubject->teacher->user->name}' is already teaching another class (".
                    "{$teacherClash->classSubject->schoolClass->full_name}) between ".
                    substr($teacherClash->start_time, 0, 5).' and '.substr($teacherClash->end_time, 0, 5).'.',
            ]);
        }
    }
}
