<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accountant\RecordPaymentRequest;
use App\Http\Requests\Accountant\ReversePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\InstitutionDetails;
use App\Support\NumberToWords;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('payments.create'), 403);

        $invoiceId = $request->query('invoice_id');
        $selectedInvoice = null;

        if ($invoiceId) {
            $selectedInvoice = Invoice::with(['student.user', 'student.class', 'term'])->findOrFail($invoiceId);
        }

        $pendingInvoices = Invoice::with(['student.user', 'student.class'])
            ->where('balance', '>', 0)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->orderByDesc('id')
            ->get();

        return view('Accountant.AccountantPaymentsCreate', compact('selectedInvoice', 'pendingInvoices') + [
            'financeRoutePrefix' => $this->financeRoutePrefix(),
        ]);
    }

    public function store(RecordPaymentRequest $request): RedirectResponse
    {
        $payment = PaymentService::recordPayment(
            (int) $request->input('invoice_id'),
            $request->input('amount'),
            (string) $request->input('method'),
            $request->input('reference'),
            (string) $request->input('paid_at')
        );

        return redirect()->route($this->financeRoutePrefix().'.receipt.show', $payment)
            ->with('success', "Payment of {$payment->amount} recorded. Receipt: {$payment->receipt_no}");
    }

    public function receipt(Payment $payment): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->can('payments.read'), 403);

        $payment->load(['invoice.student.user', 'invoice.student.class', 'invoice.term', 'receiver']);

        return view('Accountant.AccountantReceiptShow', compact('payment') + [
            'financeRoutePrefix' => $this->financeRoutePrefix(),
        ]);
    }

    public function receiptPdf(Payment $payment): Response
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->can('payments.read'), 403);

        $payment->load(['invoice.student.user', 'invoice.student.class', 'invoice.term', 'receiver']);

        return Pdf::setOptions(['isRemoteEnabled' => false])
            ->loadView('Pdf.PdfReceipt', [
                'payment' => $payment,
                'institution' => InstitutionDetails::values(),
                'amountInWords' => NumberToWords::rupees($payment->amount),
            ])
            ->setPaper('a4')
            ->download('receipt-'.$payment->receipt_no.'.pdf');
    }

    public function reverse(ReversePaymentRequest $request, Payment $payment): RedirectResponse
    {
        PaymentService::reversePayment($payment, (string) $request->input('reversal_reason'), $request->user()->id);

        return back()->with('success', "Payment {$payment->receipt_no} has been reversed and invoice balance restored.");
    }

    private function financeRoutePrefix(): string
    {
        return request()->routeIs('accountant.*') ? 'accountant' : 'admin';
    }
}
