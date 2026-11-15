<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'person_category',
        'from_branch_id',
        'to_branch_id',
        'previous_admission_no',
        'new_admission_no',
        'transfer_date',
        'reason',
        'transferred_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id')->withoutGlobalScopes();
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id')->withoutGlobalScopes();
    }

    public function transferredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'person_id')->withoutGlobalScopes();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'person_id')->withoutGlobalScopes();
    }
}
