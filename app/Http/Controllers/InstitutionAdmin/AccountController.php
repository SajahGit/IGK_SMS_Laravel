<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = Account::with('parent')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->string('q').'%')->orWhere('code', 'like', '%'.$request->string('q').'%')))
            ->orderBy('code')->paginate(20)->withQueryString();

        return view('Accounting.AccountsIndex', compact('accounts'));
    }

    public function create(): View
    {
        return view('Accounting.AccountsForm', ['account' => new Account(['is_active' => true]), 'parents' => Account::orderBy('code')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = Account::create($this->validated($request));
        AuditLogger::log('created_account', $account, null, $account->toArray());

        return redirect()->route('admin.accounts.index')->with('success', 'Account created successfully.');
    }

    public function edit(Account $account): View
    {
        return view('Accounting.AccountsForm', ['account' => $account, 'parents' => Account::where('id', '!=', $account->id)->orderBy('code')->get()]);
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $before = $account->toArray();
        $account->update($this->validated($request, $account));
        AuditLogger::log('updated_account', $account, $before, $account->toArray());

        return redirect()->route('admin.accounts.index')->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        if ($account->lines()->exists() || Account::where('parent_id', $account->id)->exists()) {
            return back()->withErrors(['account' => 'Accounts with postings or child accounts cannot be deleted. Deactivate it instead.']);
        }
        $before = $account->toArray();
        $account->delete();
        AuditLogger::log('deleted_account', $account, $before, null);

        return redirect()->route('admin.accounts.index')->with('success', 'Account deleted.');
    }

    private function validated(Request $request, ?Account $account = null): array
    {
        $code = $request->input('code');
        if (is_string($code)) {
            $request->merge(['code' => strtoupper($code)]);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/\A[A-Z0-9-]+\z/', Rule::unique('accounts', 'code')->ignore($account?->id)],
            'name' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z][A-Za-z0-9\s\-()&]*\z/'],
            'type' => ['required', Rule::in(array_keys(Account::TYPES))],
            'parent_id' => ['nullable', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255', 'regex:/\A[^=|;\\\\]*\z/'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
