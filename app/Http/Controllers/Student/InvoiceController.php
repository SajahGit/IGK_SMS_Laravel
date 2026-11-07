<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\InvoicePdf;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile linked to your account.');

        $invoices = Invoice::with(['term', 'payments.receiver'])
            ->where('student_id', $student->id)
            ->where('status', '!=', 'draft')
            ->orderByDesc('issue_date')
            ->get();

        // Cancelled invoices are void and are left out of every total.
        $liveInvoices = $invoices->where('status', '!=', 'cancelled');
        $totalBilled = $liveInvoices->sum('total');
        $totalPaid = $liveInvoices->sum('amount_paid');
        $totalDue = $liveInvoices->sum('balance');

        return view('Student.StudentInvoicesIndex', compact('student', 'invoices', 'totalBilled', 'totalPaid', 'totalDue'));
    }

    public function pdf(Invoice $invoice): Response
    {
        $student = auth()->user()->student;
        abort_unless($student && $invoice->student_id === $student->id && ! $invoice->isDraft(), 404);

        return InvoicePdf::download($invoice);
    }
}
