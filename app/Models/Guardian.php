<?php

namespace App\Models;

use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'phone', 'nationality'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryStudents(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function scopeInActiveBranch(Builder $query): Builder
    {
        if (BranchContext::activeBranchId() === null) {
            return $query;
        }

        return $query->where(function (Builder $query): void {
            $query->whereHas('students')
                ->orWhereHas('primaryStudents');
        });
    }
}
