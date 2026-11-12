<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEntry extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'base_salary',
        'allowance',
        'deduction',
        'employee_epf',
        'employer_epf',
        'employer_etf',
        'apit_deduction',
        'net_pay',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'allowance' => 'decimal:2',
            'deduction' => 'decimal:2',
            'employee_epf' => 'decimal:2',
            'employer_epf' => 'decimal:2',
            'employer_etf' => 'decimal:2',
            'apit_deduction' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
