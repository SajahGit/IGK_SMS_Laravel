<?php

namespace App\Models\Scopes;

use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchOwned implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        if ($user->isParent()) {
            return;
        }

        if (BranchContext::isGlobalAdmin($user)) {
            $branchId = BranchContext::activeBranchId($user);
            if ($branchId !== null) {
                $builder->where($model->qualifyColumn('branch_id'), $branchId);
            }

            return;
        }

        if ($user->branch_id === null) {
            if (BranchContext::hasActiveBranches()) {
                $builder->whereRaw('1 = 0');
            }

            return;
        }

        $builder->where($model->qualifyColumn('branch_id'), $user->branch_id);
    }
}
