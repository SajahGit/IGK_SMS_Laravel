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

        $activeBranch = $branches->firstWhere('id', (int) $request->session()->get('active_branch_id'));
        if (! $activeBranch) {
            $activeBranch = $branches->first();
            if ($activeBranch) {
                $request->session()->put('active_branch_id', $activeBranch->id);
            } else {
                $request->session()->forget('active_branch_id');
            }
        }

        View::share('branchContextOptions', $branches);
        View::share('activeBranch', $activeBranch);

        return $next($request);
    }
}
