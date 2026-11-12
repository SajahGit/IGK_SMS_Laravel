<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\BranchContext;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'address', 'city', 'phone', 'email', 'principal_name', 'is_main', 'is_active'];

    protected static function booted(): void
    {
        static::addGlobalScope('assigned-branch', function (Builder $builder): void {
            $user = auth()->user();
            if (! $user || $user->isParent() || BranchContext::isGlobalAdmin($user)) {
                return;
            }

            if ($user->branch_id === null) {
                if (BranchContext::hasActiveBranches()) {
                    $builder->whereRaw('1 = 0');
                }

                return;
            }

            $builder->whereKey($user->branch_id);
        });
    }

    protected function casts(): array
    {
        return ['is_main' => 'boolean', 'is_active' => 'boolean'];
    }
}
