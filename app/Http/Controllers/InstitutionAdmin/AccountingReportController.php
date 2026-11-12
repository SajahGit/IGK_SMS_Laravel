<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountingReportController extends Controller
{
    public function incomeExpense(Request $request): View
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfYear()->toDateString();
        $to = $request->date('to')?->toDateString() ?? today()->toDateString();
        abort_if($from > $to, 422, 'Start date must be on or before end date.');

        $accounts = Account::query()->whereIn('type', ['income', 'expense'])->orderBy('code')->get();
        $rows = $accounts->map(function (Account $account) use ($from, $to, $request) {
            $lines = JournalLine::query()
                ->where('account_id', $account->id)
                ->whereHas('entry', fn ($query) => $query
                    ->where('status', 'posted')
                    ->whereDate('entry_date', '>=', $from)
                    ->whereDate('entry_date', '<=', $to)
                    ->when($request->filled('branch_id'), fn ($branch) => $branch->where('branch_id', $request->integer('branch_id'))));
            $normalCredit = $account->type === 'income';
            $amount = $normalCredit
                ? bcsub((string) $lines->sum('credit'), (string) $lines->sum('debit'), 2)
                : bcsub((string) $lines->sum('debit'), (string) $lines->sum('credit'), 2);

            return ['account' => $account, 'amount' => $amount];
        })->filter(fn (array $row) => bccomp($row['amount'], '0.00', 2) !== 0);

        $income = $rows->where('account.type', 'income')->values();
        $expenses = $rows->where('account.type', 'expense')->values();
        $totalIncome = $income->reduce(fn (string $total, array $row) => bcadd($total, $row['amount'], 2), '0.00');
        $totalExpenses = $expenses->reduce(fn (string $total, array $row) => bcadd($total, $row['amount'], 2), '0.00');
        $netOperatingSurplus = bcsub($totalIncome, $totalExpenses, 2);
        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get();

        return view('Accounting.IncomeExpense', compact('from', 'to', 'income', 'expenses', 'totalIncome', 'totalExpenses', 'netOperatingSurplus', 'branches'));
    }

    public function trialBalance(Request $request): View
    {
        $to = $request->input('to');
        $rows = Account::orderBy('code')->get()->map(function (Account $account) use ($to) {
            $q = JournalLine::where('account_id', $account->id)
                ->whereHas('entry', fn ($e) => $e->where('status', 'posted')->when($to, fn ($w) => $w->whereDate('entry_date', '<=', $to)));
            $debit = (string) ($q->sum('debit') ?: '0');
            $credit = (string) ($q->sum('credit') ?: '0');
            $net = bcsub($debit, $credit, 2);

            return [
                'account' => $account,
                'debit' => bccomp($net, '0', 2) > 0 ? $net : '0.00',
                'credit' => bccomp($net, '0', 2) < 0 ? ltrim($net, '-') : '0.00',
            ];
        })->filter(fn ($r) => bccomp($r['debit'], '0', 2) !== 0 || bccomp($r['credit'], '0', 2) !== 0)->values();

        $totalDebit = $rows->reduce(fn ($c, $r) => bcadd($c, $r['debit'], 2), '0.00');
        $totalCredit = $rows->reduce(fn ($c, $r) => bcadd($c, $r['credit'], 2), '0.00');

        return view('Accounting.TrialBalance', compact('rows', 'totalDebit', 'totalCredit', 'to'));
    }

    public function ledger(Request $request): View
    {
        $accounts = Account::orderBy('code')->get();
        $account = $request->filled('account_id') ? Account::find($request->integer('account_id')) : null;
        $lines = collect();
        $running = '0.00';

        if ($account) {
            $lines = JournalLine::with('entry')
                ->where('account_id', $account->id)
                ->whereHas('entry', fn ($e) => $e->where('status', 'posted')
                    ->when($request->filled('from'), fn ($w) => $w->whereDate('entry_date', '>=', $request->date('from')))
                    ->when($request->filled('to'), fn ($w) => $w->whereDate('entry_date', '<=', $request->date('to'))))
                ->get()->sortBy(fn ($l) => $l->entry->entry_date->format('Ymd').str_pad((string) $l->id, 10, '0', STR_PAD_LEFT))->values();

            foreach ($lines as $line) {
                $delta = $account->isDebitNormal() ? bcsub((string) $line->debit, (string) $line->credit, 2) : bcsub((string) $line->credit, (string) $line->debit, 2);
                $running = bcadd($running, $delta, 2);
                $line->running = $running;
            }
        }

        return view('Accounting.Ledger', compact('accounts', 'account', 'lines', 'running'));
    }

    public function branchSummary(): View
    {
        $rows = Branch::orderByDesc('is_main')->orderBy('name')->get()->map(fn (Branch $b) => [
            'branch' => $b,
            'students' => DB::table('students')->where('branch_id', $b->id)->count(),
            'classes' => DB::table('classes')->where('branch_id', $b->id)->count(),
            'employees' => DB::table('employees')->where('branch_id', $b->id)->count(),
            'invoiced' => (string) (DB::table('invoices')->where('branch_id', $b->id)->whereNotIn('status', ['draft', 'cancelled'])->sum('total') ?: '0'),
            'collected' => (string) (DB::table('invoices')->where('branch_id', $b->id)->whereNotIn('status', ['draft', 'cancelled'])->sum('amount_paid') ?: '0'),
        ]);

        // Phase 2: operational footprint per campus (Inventory, Hostel, Transport, Library, Payroll).
        $footprint = Branch::orderByDesc('is_main')->orderBy('name')->get()->map(fn (Branch $b) => [
            'branch' => $b,
            'inventory' => DB::table('inventory_items')->where('branch_id', $b->id)->count(),
            'suppliers' => DB::table('suppliers')->where('branch_id', $b->id)->count(),
            'purchase_orders' => DB::table('purchase_orders')->where('branch_id', $b->id)->count(),
            'hostels' => DB::table('hostels')->where('branch_id', $b->id)->count(),
            'vehicles' => DB::table('transport_vehicles')->where('branch_id', $b->id)->count(),
            'library_copies' => DB::table('library_copies')->where('branch_id', $b->id)->count(),
            'payroll_runs' => DB::table('payroll_runs')->where('branch_id', $b->id)->count(),
        ]);

        return view('Branches.BranchSummary', compact('rows', 'footprint'));
    }
}
