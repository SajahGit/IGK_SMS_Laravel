<?php

namespace App\Models;

use App\Models\Scopes\BranchOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'class_id', 'program_id', 'academic_year_id', 'guardian_id', 'branch_id',
        'admission_no', 'admission_date', 'dob', 'gender', 'national_id', 'phone', 'status',
        'nationality', 'document_type', 'issuing_country', 'document_number',
    ];

    protected $casts = ['dob' => 'date', 'admission_date' => 'date'];

    public function setDocumentNumberAttribute(?string $value): void
    {
        $this->attributes['document_number'] = $value;
        $this->attributes['national_id'] = $value;
    }

    public function getDocumentNumberAttribute(): ?string
    {
        return $this->attributes['document_number'] ?? $this->attributes['national_id'] ?? null;
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchOwned);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function classSubjects(): BelongsToMany
    {
        return $this->belongsToMany(ClassSubject::class, 'student_subject_enrollments')
            ->withPivot(['status', 'enrolled_at', 'dropped_at', 'created_by'])
            ->withTimestamps();
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryGuardian(): BelongsToMany
    {
        return $this->guardians()->wherePivot('is_primary', true);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function subjectResults(): HasMany
    {
        return $this->hasMany(SubjectResult::class);
    }

    public function termResults(): HasMany
    {
        return $this->hasMany(TermResult::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function transportAssignments(): HasMany
    {
        return $this->hasMany(StudentTransportAssignment::class);
    }

    public function hostelAssignments(): HasMany
    {
        return $this->hasMany(HostelAssignment::class);
    }
}
