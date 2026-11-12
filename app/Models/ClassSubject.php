<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClassSubject extends Model
{
    protected $table = 'class_subject';

    protected $fillable = ['class_id', 'subject_id', 'teacher_id', 'term_id'];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function learningCourse(): HasOne
    {
        return $this->hasOne(LmsCourse::class, 'class_subject_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_subject_enrollments')
            ->withPivot(['status', 'enrolled_at', 'dropped_at', 'created_by'])
            ->withTimestamps();
    }

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class);
    }
}
