<?php

namespace App\Models;

use App\Models\Scopes\BranchOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_no',
        'user_id',
        'teacher_id',
        'department_id',
        'job_title',
        'hire_date',
        'employment_status',
        'base_salary',
        'apit_mode',
        'branch_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchOwned);
    }

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'base_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }
}
