<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Services\JournalService;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashflowEntryController extends Controller
{
    public function create(Request $request): View
    {
        $type = $request->query('type', 'expense');
        abort_unless(in_array($type, ['income', 'expense'], true), 404);

        return view('Accounting.CashflowForm', [
            'type' => $type,
            'categories' => Account::query()->where('type', $type)->where('is_active', true)->orderBy('code')->get(),
            'cashAccounts' => Account::query()->whereIn('code', ['1000', '1010'])->where('is_active', true)->orderBy('code')->get(),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'reference' => $request->filled('reference')
                ? strtoupper((string) $request->input('reference'))
                : null,
        ]);
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:255', 'regex:/\A[A-Za-z][A-Za-z0-9\s.&\'():!-]*\z/'],
            'reference' => ['nullable', 'string', 'max:40', 'regex:/\A[A-Z0-9\/-]*\z/'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99', 'decimal:0,2'],
            'category_account_id' => ['required', 'exists:accounts,id'],
            'cash_account_id' => ['required', 'exists:accounts,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        if (! Money::hasValidScale($data['amount'])) {
            throw ValidationException::withMessages(['amount' => 'Amount must have at most two decimal places.']);
        }

        $category = Account::query()->where('is_active', true)->findOrFail($data['category_account_id']);
        if ($category->type !== $data['type']) {
            throw ValidationException::withMessages(['category_account_id' => 'Choose an account matching the income or expense type.']);
        }
        $cashAccount = Account::query()->where('is_active', true)->findOrFail($data['cash_account_id']);
        if ($cashAccount->type !== 'asset' || ! in_array($cashAccount->code, ['1000', '1010'], true)) {
            throw ValidationException::withMessages(['cash_account_id' => 'Choose Cash in Hand or Bank Account.']);
        }

        $amount = Money::add($data['amount'], '0.00');
        $lines = $data['type'] === 'income'
            ? [
                ['account_id' => $cashAccount->id, 'debit' => $amount, 'memo' => 'Cash/bank receipt'],
                ['account_id' => $category->id, 'credit' => $amount, 'memo' => 'Other school income'],
            ]
            : [
                ['account_id' => $category->id, 'debit' => $amount, 'memo' => 'School operating expense'],
                ['account_id' => $cashAccount->id, 'credit' => $amount, 'memo' => 'Cash/bank payment'],
            ];

        $entry = JournalService::post([
            'entry_date' => $data['entry_date'],
            'description' => $data['description'],
            'reference' => $data['reference'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
        ], $lines, 'manual_'.$data['type']);
        AuditLogger::log('recorded_school_'.$data['type'], $entry, null, [
            'amount' => $amount,
            'category_account_id' => $category->id,
            'cash_account_id' => $cashAccount->id,
            'branch_id' => $entry->branch_id,
        ]);

        return redirect()->route('admin.journals.show', $entry)
            ->with('success', ucfirst($data['type']).' recorded in the general ledger.');
    }
}
