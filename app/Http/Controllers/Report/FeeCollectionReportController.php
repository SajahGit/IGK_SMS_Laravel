<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Term;
use App\Support\CsvStreamer;
use App\Support\Money;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeeCollectionReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->can('reports.financial'), 403);

        [$from, $to, $termId, $programId, $method, $payments, $totalCollected, $byMethod, $terms, $programs] = $this->data($request);

        return view('Reports.ReportFeeCollection', compact('from', 'to', 'termId', 'programId', 'method', 'payments', 'totalCollected', 'byMethod', 'terms', 'programs') + [
            'reportRoutePrefix' => request()->routeIs('accountant.*') ? 'accountant.reports' : 'reports',
            'financeRoutePrefix' => request()->routeIs('accountant.*') ? 'accountant' : 'admin',
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->can('reports.financial'), 403);

        [$from, $to, $termId, $programId, $method] = $this->filters($request);

        // NFR-1: stream rows via a cursor instead of loading the whole
        // dataset into memory, so a 5,000-row export doesn't grow memory.
        $rows = $this->query($from, $to, $termId, $programId, $method)->cursor();

        return CsvStreamer::download(
            "fee-collection-{$from}-to-{$to}.csv",
            ['Receipt No', 'Date', 'Term', 'Program', 'Invoice No', 'Student Details', 'Class', 'Amount (LKR)', 'Method', 'Reference', 'Cashier', 'Status'],
            $rows,
            function (Payment $p) {
                return [
                    $p->receipt_no,
                    $p->paid_at->format('Y-m-d'),
                    $p->invoice->term?->name ?? '—',
                    $p->invoice->student->program?->name ?? $p->invoice->student->class?->program?->name ?? '—',
                    $p->invoice->invoice_no,
                    $p->invoice->student->user->name.' (Adm: '.$p->invoice->student->admission_no.')',
                    $p->invoice->student->class?->full_name ?? '—',
                    $p->amount,
                    ucwords(str_replace('_', ' ', $p->method)),
                    $p->reference ?: '—',
                    $p->receiver?->name ?: 'System',
                    $p->isReversed() ? 'Reversed' : 'Received',
                ];
            }
        );
    }

    /** @return array{0:string,1:string,2:?int,3:?int,4:?string} */
    private function filters(Request $request): array
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'method' => ['nullable', 'in:cash,bank_transfer,cheque'],
        ]);

        return [
            $filters['from'] ?? today()->startOfMonth()->toDateString(),
            $filters['to'] ?? today()->toDateString(),
            $filters['term_id'] ?? null,
            $filters['program_id'] ?? null,
            $filters['method'] ?? null,
        ];
    }

    private function query(string $from, string $to, ?int $termId, ?int $programId, ?string $method)
    {
        return Payment::with(['invoice.term', 'invoice.student.user', 'invoice.student.class.program', 'invoice.student.program', 'receiver'])
            ->whereHas('invoice.student.user')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->when($method, fn ($q) => $q->where('method', $method))
            ->when($termId, fn ($q) => $q->whereHas('invoice', fn ($invoice) => $invoice->where('term_id', $termId)))
            ->when($programId, fn ($q) => $q->whereHas('invoice.student', fn ($student) => $student
                ->where('program_id', $programId)
                ->orWhereHas('class', fn ($class) => $class->where('program_id', $programId))))
            ->orderByDesc('paid_at');
    }

    private function data(Request $request): array
    {
        [$from, $to, $termId, $programId, $method] = $this->filters($request);

        $payments = $this->query($from, $to, $termId, $programId, $method)->get();

        $activePayments = $payments->whereNull('reversed_at');

        $totalCollected = '0.00';
        foreach ($activePayments as $p) {
            $totalCollected = Money::add($totalCollected, $p->amount);
        }

        $byMethod = $activePayments->groupBy('method')->map(function ($items) {
            $sum = '0.00';
            foreach ($items as $item) {
                $sum = Money::add($sum, $item->amount);
            }

            return [
                'count' => $items->count(),
                'sum' => $sum,
            ];
        });

        return [
            $from, $to, $termId, $programId, $method, $payments, $totalCollected, $byMethod,
            Term::orderByDesc('start_date')->get(), Program::orderBy('name')->get(),
        ];
    }
}
