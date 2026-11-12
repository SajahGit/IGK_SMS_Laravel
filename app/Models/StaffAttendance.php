<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    protected $table = 'staff_attendance';

    protected $fillable = [
        'teacher_id', 'staff_name', 'attendance_date', 'check_in', 'check_out', 'notes', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
