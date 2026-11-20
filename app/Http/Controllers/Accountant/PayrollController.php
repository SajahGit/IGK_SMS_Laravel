<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAccountant(), 403);
        $branchId = BranchContext::activeBranchId($request->user());
        $runs = PayrollRun::withoutGlobalScopes()
            ->when($branchId !== null, fn (Builder $query) => $query->whereHas('entries', fn (Builder $entries) => $this->scopeEntriesToBranch($entries, $branchId)))
            ->withCount(['entries' => fn (Builder $entries) => $entries
                ->when($branchId !== null, fn (Builder $query) => $this->scopeEntriesToBranch($query, $branchId))])
            ->with('generator:id,name')
            ->orderByDesc('period_start')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('Accountant.PayrollIndex', compact('runs'));
    }

    public function show(Request $request, int $payrollRun): View
    {
        abort_unless($request->user()->isAccountant(), 403);
        $branchId = BranchContext::activeBranchId($request->user());
        $runQuery = PayrollRun::withoutGlobalScopes();
        if ($branchId !== null) {
            $runQuery->whereHas('entries', fn (Builder $entries) => $this->scopeEntriesToBranch($entries, $branchId));
        }
        $payrollRun = $runQuery->findOrFail($payrollRun);
        if ($branchId !== null) {
            abort_unless($this->scopeEntriesToBranch($payrollRun->entries()->getQuery(), $branchId)->exists(), 404);
        }

        $payrollRun->load([
            'entries' => function (HasMany $entries) use ($branchId): void {
                if ($branchId !== null) {
                    $this->scopeEntriesToBranch($entries->getQuery(), $branchId);
                }
                $entries->with(['employee.user', 'employee.teacher.user', 'employee.department', 'employee.branch']);
            },
        ]);

        return view('Accountant.PayrollShow', compact('payrollRun'));
    }

    private function scopeEntriesToBranch(Builder $entries, int $branchId): Builder
    {
        return $entries->whereExists(fn ($query) => $query
            ->selectRaw('1')
            ->from('employees')
            ->whereColumn('employees.id', 'payroll_entries.employee_id')
            ->where('employees.branch_id', $branchId));
    }
}
