<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Support\CsvStreamer;
use App\Support\Money;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DefaultersReportController extends Controller
{
    public function index(Request $request)
    {
        [$filters, $classes, $terms, $programs, $invoices, $totalDefaulters, $totalBalance] = $this->data($request);

        return view('Reports.ReportDefaulters', compact(
            'filters', 'classes', 'terms', 'programs', 'invoices', 'totalDefaulters', 'totalBalance'
        ) + [
            'reportRoutePrefix' => request()->routeIs('accountant.*') ? 'accountant.reports' : 'reports',
            'financeRoutePrefix' => request()->routeIs('accountant.*') ? 'accountant' : 'admin',
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('reports.financial'), 403);

        $filters = $this->validatedFilters($request);

        // NFR-1: stream rows via a cursor instead of loading the whole
        // dataset into memory, so a 5,000-row export doesn't grow memory.
        $rows = $this->query($filters)->cursor();

        return CsvStreamer::download(
            'fee-defaulters-report.csv',
            ['Invoice No', 'Term', 'Program', 'Student Name', 'Class', 'Parent / Contact', 'Due Date', 'Total Billed', 'Amount Paid', 'Balance Due', 'Days Overdue'],
            $rows,
            function (Invoice $invoice) {
                $guardian = $this->primaryGuardian($invoice->student);
                $guardianContact = trim(implode(' - ', array_filter([
                    $guardian?->user?->name,
                    $guardian?->phone,
                ]))) ?: '—';

                return [
                    $invoice->invoice_no,
                    $invoice->term?->name ?? '—',
                    $invoice->student->program?->name ?? $invoice->student->class?->program?->name ?? '—',
                    $invoice->student->user->name.' ('.$invoice->student->admission_no.')',
                    $invoice->student->class?->full_name ?? '—',
                    $guardianContact,
                    $invoice->due_date->toDateString(),
                    $invoice->total,
                    $invoice->amount_paid,
                    $invoice->balance,
                    $this->daysOverdue($invoice),
                ];
            }
        );
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'overdue_only' => ['nullable', 'boolean'],
            'overdue_days' => ['nullable', 'integer', 'min:0'],
            'min_balance' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function query(array $filters)
    {
        $overdueDays = (int) ($filters['overdue_days'] ?? 0);
        $minimumBalance = $filters['min_balance'] ?? 0;

        return Invoice::with([
            'term',
            'student.user',
            'student.class.program',
            'student.program',
            'student.guardians.user',
        ])
            ->where('balance', '>', 0)
            ->when((float) $minimumBalance > 0, fn ($query) => $query->where('balance', '>=', $minimumBalance))
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->when(isset($filters['class_id']), fn ($query) => $query->whereHas('student', fn ($student) => $student->where('class_id', $filters['class_id'])))
            ->when(isset($filters['term_id']), fn ($query) => $query->where('term_id', $filters['term_id']))
            ->when(isset($filters['program_id']), fn ($query) => $query->whereHas('student', fn ($student) => $student
                ->where('program_id', $filters['program_id'])
                ->orWhereHas('class', fn ($class) => $class->where('program_id', $filters['program_id']))))
            ->when(! empty($filters['overdue_only']), fn ($query) => $query->whereDate('due_date', '<', today()))
            ->when($overdueDays > 0, fn ($query) => $query->whereDate('due_date', '<=', today()->subDays($overdueDays)))
            ->orderBy('due_date');
    }

    private function data(Request $request): array
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('reports.financial'), 403);
        $filters = $this->validatedFilters($request);

        $invoices = $this->query($filters)->get();
        $totalBalance = '0.00';
        foreach ($invoices as $invoice) {
            $totalBalance = Money::add($totalBalance, $invoice->balance);
        }

        return [
            $filters,
            SchoolClass::with('program')->orderBy('name')->orderBy('section')->get(),
            Term::orderByDesc('start_date')->get(),
            Program::orderBy('name')->get(),
            $invoices,
            $invoices->pluck('student_id')->unique()->count(),
            $totalBalance,
        ];
    }

    private function primaryGuardian($student)
    {
        return $student->guardians->firstWhere('pivot.is_primary', true) ?? $student->guardians->first();
    }

    private function daysOverdue(Invoice $invoice): int
    {
        return today()->gt($invoice->due_date) ? abs((int) today()->diffInDays($invoice->due_date)) : 0;
    }
}
