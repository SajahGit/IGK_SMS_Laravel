<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Aggregate in SQL: never load every invoice / payment row just to add them up.
        $live = Invoice::live();
        $invoiceCount = (clone $live)->count();
        $totalBilled = (clone $live)
            ->selectRaw('currency, SUM(total) as amount')
            ->groupBy('currency')
            ->pluck('amount', 'currency')
            ->all();
        $totalOutstanding = (clone $live)
            ->selectRaw('currency, SUM(balance) as amount')
            ->groupBy('currency')
            ->pluck('amount', 'currency')
            ->all();
        $visiblePayments = Payment::whereHas('invoice.student.user');
        $totalCollected = (clone $visiblePayments)
            ->whereNull('reversed_at')
            ->selectRaw('currency, SUM(amount) as amount')
            ->groupBy('currency')
            ->pluck('amount', 'currency')
            ->all();

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
