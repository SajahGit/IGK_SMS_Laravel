<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    // "date" is deliberately NOT cast, so updateOrCreate(['date' => 'Y-m-d']) always matches.
    protected $fillable = [
        'student_id', 'class_id', 'attendance_session_id', 'date',
        'status', 'remarks', 'marked_by', 'marked_at', 'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'marked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
