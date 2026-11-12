<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Aggregate in SQL: never load every invoice / payment row just to add them up.
        $live = Invoice::live();
        $invoiceCount = (clone $live)->count();
        $totalBilled = Money::add((clone $live)->sum('total'), '0.00');
        $totalOutstanding = Money::add((clone $live)->sum('balance'), '0.00');
        $visiblePayments = Payment::whereHas('invoice.student.user');
        $totalCollected = Money::add((clone $visiblePayments)->whereNull('reversed_at')->sum('amount'), '0.00');

        return view('Accountant.AccountantDashboard', [
            'invoiceCount' => $invoiceCount,
            'totalBilled' => $totalBilled,
            'totalCollected' => $totalCollected,
            'totalOutstanding' => $totalOutstanding,
            'recentPayments' => $visiblePayments->with('invoice.student.user')
                ->latest('paid_at')
                ->take(5)
                ->get(),
        ]);
    }
}
