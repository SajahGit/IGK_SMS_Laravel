<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accountant\CancelInvoiceRequest;
use App\Http\Requests\Accountant\GenerateInvoicesRequest;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\SavedFilter;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Services\InvoiceService;
use App\Support\InvoicePdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('invoices.read'), 403);

        $status = $request->query('status');
        $classId = $request->query('class_id');
        $search = $request->query('q');

        $classes = SchoolClass::orderBy('name')->orderBy('section')->get();

        $invoices = Invoice::with(['student.user', 'student.class', 'term', 'branch'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($classId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('class_id', $classId)))
            ->when($search, function ($query, $s) {
                $query->where(function ($w) use ($s) {
                    $w->where('invoice_no', 'like', "%{$s}%")
                        ->orWhereHas('student', fn ($st) => $st->where('admission_no', 'like', "%{$s}%")
                            ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")));
                });
            })
            ->orderByDesc('updated_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Cancelled invoices are void: they are excluded from every headline figure.
        $live = Invoice::live();
        $totalBilled = (clone $live)->selectRaw('currency, SUM(total) as amount')->groupBy('currency')->pluck('amount', 'currency')->all();
        $totalCollected = (clone $live)->selectRaw('currency, SUM(amount_paid) as amount')->groupBy('currency')->pluck('amount', 'currency')->all();
        $totalOutstanding = (clone $live)->selectRaw('currency, SUM(balance) as amount')->groupBy('currency')->pluck('amount', 'currency')->all();

        return view('Accountant.AccountantInvoicesIndex', compact(
            'invoices',
            'classes',
            'status',
            'classId',
            'search',
            'totalBilled',
            'totalCollected',
            'totalOutstanding'
        ) + [
            'financeRoutePrefix' => $this->financeRoutePrefix(),
            'savedFilters' => SavedFilter::where('user_id', $request->user()->id)
                ->where('scope', 'invoices')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('invoices.create'), 403);

        $classes = SchoolClass::with(['program', 'academicYear'])
            ->withCount('students')->orderBy('name')->orderBy('section')->get();
        $selectedClassId = $request->query('class_id') ?: ($classes->first()?->id ?? null);
        $class = $classes->firstWhere('id', (int) $selectedClassId)
            ?? ($selectedClassId ? SchoolClass::with(['program', 'academicYear'])->find($selectedClassId) : null);
        $termsQuery = Term::with('academicYear')->orderBy('sequence');
        if ($class?->academic_year_id) {
            $termsQuery->where('academic_year_id', $class->academic_year_id);
        }
        $terms = $termsQuery->get();
        $selectedTermId = $request->query->has('term_id')
            ? ($request->query('term_id') ? (int) $request->query('term_id') : null)
            : $terms->firstWhere('is_current', true)?->id;
        if ($selectedTermId && ! $terms->contains('id', $selectedTermId)) {
            $selectedTermId = $terms->firstWhere('is_current', true)?->id;
        }

        $feeStructures = collect();
        $availableCurrencies = config('school.currencies', ['LKR']);
        $billingCurrency = null;
        if ($selectedClassId) {
            $termId = $selectedTermId ? (int) $selectedTermId : null;
            $academicYearId = $termId
                ? Term::whereKey($termId)->value('academic_year_id')
                : ($class?->academic_year_id ?? AcademicYear::where('is_current', true)->value('id'));

            $feeStructures = FeeStructure::with('items')
                ->billableFor($class?->program_id, $academicYearId, $selectedClassId, $termId)
                ->get();

            $programCurrencies = config('school.program_currencies', []);
            $programCode = $class?->program?->code;
            $billingCurrency = $programCode ? ($programCurrencies[$programCode] ?? null) : null;
            if ($billingCurrency) {
                $availableCurrencies = [strtoupper($billingCurrency)];
            }
        }

        $selectedCurrency = strtoupper((string) $request->query('currency', ''));
        if (! in_array($selectedCurrency, $availableCurrencies, true)) {
            $selectedCurrency = $billingCurrency
                ? strtoupper($billingCurrency)
                : ($feeStructures->first()?->currency ?? config('school.default_currency', 'LKR'));
        }
        $feeStructures = $feeStructures
            ->where('currency', $selectedCurrency)
            ->values();

        return view('Accountant.AccountantInvoicesCreate', compact(
            'classes',
            'terms',
            'selectedClassId',
            'selectedTermId',
            'selectedCurrency',
            'availableCurrencies',
            'feeStructures'
        ) + [
            'financeRoutePrefix' => $this->financeRoutePrefix(),
        ]);
    }

    public function store(GenerateInvoicesRequest $request): RedirectResponse
    {
        $result = InvoiceService::bulkGenerateForClass(
            (int) $request->input('class_id'),
            $request->input('term_id') ? (int) $request->input('term_id') : null,
            (string) $request->input('issue_date'),
            (string) $request->input('due_date'),
            (array) $request->input('fee_structure_ids'),
            $request->input('discount') ?: '0.00',
            array_map('intval', (array) $request->input('fee_item_ids', [])),
            $request->boolean('as_draft')
        );

        $kind = $request->boolean('as_draft') ? 'created as drafts' : 'created';

        return redirect()->route($this->financeRoutePrefix().'.invoices.index', ['class_id' => $request->input('class_id')])
            ->with('success', "Invoices generated: {$result['created']} {$kind}, {$result['skipped']} skipped (already invoiced).");
    }

    public function issue(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('invoices.update'), 403);

        InvoiceService::issue($invoice);

        return redirect()->route($this->financeRoutePrefix().'.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_no} has been issued.");
    }

    public function issueDrafts(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('invoices.update'), 403);

        $data = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
        ]);

        $count = InvoiceService::issueDraftsForClass((int) $data['class_id'], ! empty($data['term_id']) ? (int) $data['term_id'] : null);

        return redirect()->route($this->financeRoutePrefix().'.invoices.index', ['class_id' => $data['class_id']])
            ->with('success', "{$count} draft invoice(s) issued.");
    }

    public function show(Invoice $invoice): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->can('invoices.read'), 403);

        $invoice->load(['student.user', 'student.class', 'student.guardian.user', 'term', 'items', 'payments.receiver']);

        return view('Accountant.AccountantInvoicesShow', compact('invoice') + [
            'financeRoutePrefix' => $this->financeRoutePrefix(),
        ]);
    }

    public function cancel(CancelInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        InvoiceService::cancel($invoice, (string) $request->input('cancellation_reason'));

        return redirect()->route($this->financeRoutePrefix().'.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_no} has been cancelled.");
    }

    public function pdf(Invoice $invoice): Response
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->can('invoices.read'), 403);

        return InvoicePdf::download($invoice);
    }

    private function financeRoutePrefix(): string
    {
        return request()->routeIs('accountant.*') ? 'accountant' : 'admin';
    }
}
