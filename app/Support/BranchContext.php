<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BranchContext
{
    public static function hasActiveBranches(): bool
    {
        return Branch::withoutGlobalScope('assigned-branch')->where('is_active', true)->exists();
    }

    public static function isGlobalAdmin(?User $user): bool
    {
        return $user !== null && ($user->isSuperAdmin() || $user->isInstitutionAdmin());
    }

    public static function activeBranchId(?User $user = null): ?int
    {
        $user ??= Auth::user();
        if (! $user) {
            return null;
        }

        if (self::isGlobalAdmin($user)) {
            $selected = session()->get('active_branch_id');
            if ($selected === 'all' && $user->isSuperAdmin()) {
                return null;
            }
            if ($selected) {
                return (int) $selected;
            }

            $mainBranchId = Branch::withoutGlobalScope('assigned-branch')
                ->where('is_active', true)
                ->orderByDesc('is_main')
                ->orderBy('id')
                ->value('id');

            return $mainBranchId === null ? null : (int) $mainBranchId;
        }

        return $user->branch_id === null ? null : (int) $user->branch_id;
    }
}
