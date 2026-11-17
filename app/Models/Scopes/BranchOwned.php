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

        if (BranchContext::canAccessAllBranches($user)) {
            $branchId = BranchContext::activeBranchId($user);
            if ($branchId !== null) {
                $builder->where($model->qualifyColumn('branch_id'), $branchId);
            }

            return;
        }

        $branchId = $user->branch_id;
        if ($branchId === null && $user->isStudent()) {
            $branchId = \Illuminate\Support\Facades\DB::table('students')->where('user_id', $user->id)->value('branch_id');
        }

        if ($branchId === null) {
            if (BranchContext::hasActiveBranches()) {
                $builder->whereRaw('1 = 0');
            }

            return;
        }

        $builder->where(function (Builder $query) use ($model, $branchId): void {
            $query->where($model->qualifyColumn('branch_id'), $branchId);
            if ($model instanceof \App\Models\CafeteriaItem || $model instanceof \App\Models\InventoryItem) {
                $query->orWhereNull($model->qualifyColumn('branch_id'));
            }
        });
    }
}
