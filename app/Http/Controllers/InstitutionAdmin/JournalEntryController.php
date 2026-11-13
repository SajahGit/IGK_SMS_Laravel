<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Services\JournalService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class JournalEntryController extends Controller
{
    public function index(Request $request): View
    {
        $entries = JournalEntry::withSum('lines as total_debit', 'debit')->with('branch')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->date('to')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->orderByDesc('entry_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('Accounting.JournalsIndex', ['entries' => $entries, 'branches' => Branch::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('Accounting.JournalsForm', [
            'accounts' => Account::where('is_active', true)->orderBy('code')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'currency' => ['required', 'string', 'size:3', \Illuminate\Validation\Rule::in(config('school.currencies', ['LKR']))],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:80'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        $lines = collect($data['lines'])->filter(fn ($l) => (float) ($l['debit'] ?? 0) > 0 || (float) ($l['credit'] ?? 0) > 0)->values()->all();

        try {
            $entry = JournalService::post($data, $lines);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }
        AuditLogger::log('posted_journal_entry', $entry, null, $entry->toArray());

        return redirect()->route('admin.journals.show', $entry)->with('success', 'Journal entry posted.');
    }

    public function show(JournalEntry $journal): View
    {
        $journal->load('lines.account', 'branch');

        return view('Accounting.JournalsShow', ['entry' => $journal]);
    }

    public function destroy(JournalEntry $journal): RedirectResponse
    {
        if (in_array($journal->source_type, ['invoice', 'payment'], true)) {
            return back()->withErrors(['journal' => 'Billing journal entries must be corrected by cancelling the invoice or reversing the payment.']);
        }

        $before = $journal->toArray();
        $journal->update(['status' => 'void']);
        AuditLogger::log('voided_journal_entry', $journal, $before, $journal->toArray());

        return redirect()->route('admin.journals.index')->with('success', 'Journal entry voided.');
    }

    public function sync(): RedirectResponse
    {
        try {
            $result = JournalService::syncFromBilling();
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['sync' => $e->getMessage()]);
        }

        return back()->with('success', "Auto-posted {$result['invoices']} invoice(s) and {$result['payments']} payment(s).");
    }
}
