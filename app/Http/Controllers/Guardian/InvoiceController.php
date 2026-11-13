<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $guardian = auth()->user()->guardian;
        abort_unless($guardian, 403, 'No guardian profile linked to your account.');

        $children = $guardian->students()->with(['user', 'class'])->get();
        $studentIds = $children->pluck('id');

        $invoices = Invoice::with(['student.user', 'student.class', 'term', 'payments'])
            ->whereIn('student_id', $studentIds)
            ->where('status', '!=', 'draft')
            ->orderByDesc('issue_date')
            ->get();

        // Cancelled invoices are void and are left out of every total.
        $liveInvoices = $invoices->where('status', '!=', 'cancelled');
        $totalBilled = \App\Support\Money::sumByCurrency($liveInvoices, 'total');
        $totalPaid = \App\Support\Money::sumByCurrency($liveInvoices, 'amount_paid');
        $totalDue = \App\Support\Money::sumByCurrency($liveInvoices, 'balance');

        return view('Guardian.GuardianInvoicesIndex', compact('children', 'invoices', 'totalBilled', 'totalPaid', 'totalDue'));
    }
}
