<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    protected $table = 'staff_attendance';

    protected $fillable = [
        'teacher_id', 'employee_id', 'branch_id', 'staff_name', 'attendance_date', 'check_in', 'check_out', 'notes', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getStatusAttribute(): string
    {
        if (! empty($this->attributes['status'])) {
            return strtolower($this->attributes['status']);
        }

        if (empty($this->check_in)) {
            return 'absent';
        }

        $notes = strtolower($this->notes ?? '');
        if (str_contains($notes, 'late')) {
            return 'late';
        }
        if (str_contains($notes, 'half') || str_contains($notes, 'half-day') || str_contains($notes, 'half day')) {
            return 'half_day';
        }

        $checkInTime = substr($this->check_in, 0, 5);
        if ($checkInTime > '08:30') {
            return 'late';
        }

        return ! empty($this->check_out) ? 'present' : 'present';
    }

    public function getCheckInFormattedAttribute(): string
    {
        if (empty($this->check_in)) {
            return '—';
        }

        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', strlen($this->check_in) === 5 ? $this->check_in.':00' : $this->check_in)->format('h:i A');
        } catch (\Throwable) {
            return substr($this->check_in, 0, 5);
        }
    }

    public function getCheckOutFormattedAttribute(): string
    {
        if (empty($this->check_out)) {
            return 'Active / In Progress';
        }

        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', strlen($this->check_out) === 5 ? $this->check_out.':00' : $this->check_out)->format('h:i A');
        } catch (\Throwable) {
            return substr($this->check_out, 0, 5);
        }
    }

    public function getWorkingHoursAttribute(): float
    {
        if (empty($this->check_in) || empty($this->check_out)) {
            return 0.0;
        }

        try {
            $in = \Carbon\Carbon::parse($this->check_in);
            $out = \Carbon\Carbon::parse($this->check_out);
            $minutes = abs((int) $out->diffInMinutes($in));

            return round($minutes / 60, 2);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    public function getWorkingHoursFormattedAttribute(): string
    {
        if (empty($this->check_in)) {
            return '—';
        }

        if (empty($this->check_out)) {
            return 'In progress';
        }

        try {
            $in = \Carbon\Carbon::parse($this->check_in);
            $out = \Carbon\Carbon::parse($this->check_out);
            $totalMinutes = abs((int) $out->diffInMinutes($in));
            $hours = intdiv($totalMinutes, 60);
            $mins = $totalMinutes % 60;

            return "{$hours}h {$mins}m";
        } catch (\Throwable) {
            return '—';
        }
    }
}
