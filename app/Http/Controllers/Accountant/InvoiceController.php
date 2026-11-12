<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accountant\CancelInvoiceRequest;
use App\Http\Requests\Accountant\GenerateInvoicesRequest;
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

        $invoices = Invoice::with(['student.user', 'student.class', 'term'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($classId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('class_id', $classId)))
            ->when($search, function ($query, $s) {
                $query->where(function ($w) use ($s) {
                    $w->where('invoice_no', 'like', "%{$s}%")
                        ->orWhereHas('student', fn ($st) => $st->where('admission_no', 'like', "%{$s}%")
                            ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")));
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Cancelled invoices are void: they are excluded from every headline figure.
        $live = Invoice::live();
        $totalBilled = (clone $live)->sum('total');
        $totalCollected = (clone $live)->sum('amount_paid');
        $totalOutstanding = (clone $live)->sum('balance');

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

        $classes = SchoolClass::withCount('students')->orderBy('name')->orderBy('section')->get();
        $terms = Term::orderBy('sequence')->get();
        $selectedClassId = $request->query('class_id') ?: ($classes->first()?->id ?? null);

        $feeStructures = collect();
        if ($selectedClassId) {
            $class = SchoolClass::find($selectedClassId);
            $termId = $request->query('term_id') ? (int) $request->query('term_id') : null;
            $academicYearId = $termId
                ? Term::whereKey($termId)->value('academic_year_id')
                : $class?->academic_year_id;

            // SOP §15.1 / US-F1: program-wide structures for this program+year, plus
            // (legacy, still supported) structures tied to this class directly.
            $feeStructures = FeeStructure::with('items')
                ->billableFor($class?->program_id, $academicYearId, $selectedClassId, $termId)
                ->get();
        }

        return view('Accountant.AccountantInvoicesCreate', compact('classes', 'terms', 'selectedClassId', 'feeStructures') + [
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
