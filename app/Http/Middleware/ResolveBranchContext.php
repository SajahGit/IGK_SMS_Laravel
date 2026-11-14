<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveBranchContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! BranchContext::isGlobalAdmin($user)) {
            View::share('branchContextOptions', collect());

            return $next($request);
        }

        $branches = Branch::withoutGlobalScope('assigned-branch')
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        if (BranchContext::isGlobalAdmin($user) && ! $request->session()->get('branch_context_initialized')) {
            $request->session()->put('active_branch_id', 'all');
            $request->session()->put('branch_context_initialized', true);
        }

        $selectedBranchId = $request->session()->get('active_branch_id');
        $activeBranch = $selectedBranchId === 'all'
            ? null
            : $branches->firstWhere('id', (int) $selectedBranchId);
        if (! $activeBranch) {
            if ($selectedBranchId !== 'all') {
                $activeBranch = $branches->first();
                if ($activeBranch) {
                    $request->session()->put('active_branch_id', $activeBranch->id);
                } else {
                    $request->session()->forget('active_branch_id');
                }
            }
        }

        View::share('branchContextOptions', $branches);
        View::share('activeBranch', $activeBranch);
        View::share('allCampusesSelected', $selectedBranchId === 'all');

        return $next($request);
    }
}
