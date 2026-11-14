<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentFinancialLedgerReportController extends Controller
{
    public function index(Request $request)
    {
        $isStudent = $request->user()->isStudent();
        abort_unless($isStudent || $request->user()->isAdmin() || $request->user()->can('reports.financial'), 403);
        $validated = $request->validate([
            'student_id' => ['nullable', 'integer'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
        ]);
        $studentId = $validated['student_id'] ?? null;
        $academicYearId = isset($validated['academic_year_id']) ? (int) $validated['academic_year_id'] : null;
        $academicYears = AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'start_date']);
        $pdfLimit = $request->attributes->get('pdf_export_limit');

        if ($isStudent) {
            $ownStudent = $request->user()->student;
            abort_unless($ownStudent, 403, 'Student account is not linked to a student profile.');
            abort_if($studentId && (int) $studentId !== $ownStudent->id, 403, 'You can only view your own ledger.');
            $studentId = $ownStudent->id;
            $students = Student::query()
                ->select(['id', 'user_id', 'class_id', 'admission_no'])
                ->with(['user:id,name', 'class:id,name,section'])
                ->whereKey($studentId)
                ->get();
        } else {
            $students = Student::query()
                ->select(['id', 'user_id', 'class_id', 'admission_no'])
                ->with(['user:id,name', 'class:id,name,section'])
                ->orderBy('admission_no')
                ->when(is_int($pdfLimit), fn ($query) => $query->limit($pdfLimit))
                ->get();
        }

        $ledger = collect();
        $selectedStudent = null;

        if ($studentId) {
            $selectedStudent = Student::query()
                ->select(['id', 'user_id', 'class_id', 'admission_no'])
                ->with(['user:id,name', 'class:id,name,section'])
                ->findOrFail($studentId);
            if ($isStudent) {
                abort_unless($selectedStudent->id === $request->user()->student->id, 403);
            }
            $events = $this->ledgerEvents($selectedStudent, $academicYearId, is_int($pdfLimit) ? $pdfLimit : null);

            $balances = [];
            $ledger = $events->map(function ($event) use (&$balances) {
                $currency = $event['currency'];
                $balances[$currency] ??= '0.00';
                if ($event['type'] === 'charge') {
                    $balances[$currency] = Money::add($balances[$currency], $event['amount']);
                } else {
                    $balances[$currency] = Money::sub($balances[$currency], $event['amount']);
                }

                return array_merge($event, ['running_balance' => $balances[$currency]]);
            });
        }

        return view('Reports.ReportStudentFinancialLedger', compact(
            'students', 'academicYears', 'selectedStudent', 'studentId', 'academicYearId', 'ledger', 'isStudent'
        ) + [
            'reportRoutePrefix' => request()->routeIs('student.ledger*')
                ? 'student'
                : (request()->routeIs('accountant.*') ? 'accountant.reports' : 'reports'),
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $isStudent = $request->user()->isStudent();
        abort_unless($isStudent || $request->user()->isAdmin() || $request->user()->can('reports.financial'), 403);
        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
        ]);
        $studentId = $validated['student_id'];
        $academicYearId = isset($validated['academic_year_id']) ? (int) $validated['academic_year_id'] : null;

        $selectedStudent = Student::with(['user', 'class'])->findOrFail($studentId);
        if ($isStudent) {
            abort_unless($request->user()->student && $selectedStudent->id === $request->user()->student->id, 403);
        }
        $events = $this->ledgerEvents($selectedStudent, $academicYearId);

        return response()->streamDownload(function () use ($events) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Description', 'Type', 'Charge', 'Payment', 'Currency', 'Running Balance']);
            $balances = [];
            foreach ($events as $event) {
                $currency = $event['currency'];
                $balances[$currency] ??= '0.00';
                if ($event['type'] === 'charge') {
                    $balances[$currency] = Money::add($balances[$currency], $event['amount']);
                    $charge = $event['amount'];
                    $payment = '—';
                } else {
                    $balances[$currency] = Money::sub($balances[$currency], $event['amount']);
                    $charge = '—';
                    $payment = $event['amount'];
                }

                fputcsv($out, [
                    $event['date'],
                    $event['description'],
                    ucfirst($event['type']),
                    $charge,
                    $payment,
                    $currency,
                    $balances[$currency],
                ]);
            }
            fclose($out);
        }, 'student-ledger-'.$selectedStudent->admission_no.'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Build a chronological ledger of charges (invoices) and payments for a student.
     */
    private function ledgerEvents(Student $student, ?int $academicYearId, ?int $limit = null): Collection
    {
        $invoiceQuery = Invoice::live()
            ->select(['id', 'student_id', 'issue_date', 'created_at', 'invoice_no', 'total', 'currency'])
            ->where('student_id', $student->id);

        if ($academicYearId) {
            $invoiceQuery->whereHas('term', fn ($q) => $q->where('academic_year_id', $academicYearId));
        }

        $eventLimit = $limit === null ? null : (int) ceil($limit / 2);
        $invoices = $invoiceQuery
            ->orderBy('issue_date')
            ->when($eventLimit !== null, fn ($query) => $query->limit($eventLimit))
            ->get();
        $charges = $invoices->map(function ($inv) {
            return [
                'date' => $inv->issue_date?->toDateString() ?? $inv->created_at->toDateString(),
                'description' => 'Invoice '.$inv->invoice_no,
                'type' => 'charge',
                'amount' => $inv->total,
                'currency' => $inv->currency,
            ];
        });

        $payments = Payment::query()
            ->select(['id', 'invoice_id', 'paid_at', 'created_at', 'receipt_no', 'amount', 'currency'])
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->whereNull('reversed_at')
            ->orderBy('paid_at')
            ->when($eventLimit !== null, fn ($query) => $query->limit($eventLimit))
            ->get()
            ->map(function ($p) {
                return [
                    'date' => $p->paid_at?->toDateString() ?? $p->created_at->toDateString(),
                    'description' => 'Receipt '.$p->receipt_no,
                    'type' => 'payment',
                    'amount' => $p->amount,
                    'currency' => $p->currency,
                ];
            });

        return $charges->concat($payments)->sortBy('date')->values();
    }
}
