<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PayHereTransaction;
use App\Services\PayHereService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayHereController extends Controller
{
    public function create(Invoice $invoice): View
    {
        $student = auth()->user()->student;
        abort_unless($student && $invoice->student_id === $student->id && ! $invoice->isDraft(), 404);

        return view('Payments.PayHereCheckout', compact('invoice'));
    }

    public function store(Request $request, Invoice $invoice, PayHereService $service): View
    {
        $student = $request->user()->student;
        abort_unless($student && $invoice->student_id === $student->id, 404);

        $customer = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'country' => ['required', 'string', 'max:80'],
        ]);

        $transaction = $service->createTransaction($invoice);
        $payload = [
            'merchant_id' => config('services.payhere.merchant_id'),
            'return_url' => route('student.payhere.return', $transaction),
            'cancel_url' => route('student.payhere.cancel', $transaction),
            'notify_url' => route('payhere.notify'),
            ...$customer,
            'order_id' => $transaction->order_id,
            'items' => 'School fees '.$invoice->invoice_no,
            'currency' => $transaction->currency,
            'amount' => number_format((float) $transaction->amount, 2, '.', ''),
        ];
        $payload['hash'] = $service->checkoutHash($payload['order_id'], $payload['amount'], $payload['currency']);

        return view('Payments.PayHereRedirect', [
            'checkoutUrl' => $service->checkoutUrl(),
            'payload' => $payload,
        ]);
    }

    public function notify(Request $request, PayHereService $service)
    {
        $service->handleNotification($request->all());

        return response('OK', 200);
    }

    public function returned(PayHereTransaction $transaction): RedirectResponse
    {
        $this->authorizeStudentTransaction($transaction);

        return redirect()->route('student.invoices')->with('status', 'Payment status will update after PayHere confirms it.');
    }

    public function cancelled(PayHereTransaction $transaction): RedirectResponse
    {
        $this->authorizeStudentTransaction($transaction);

        return redirect()->route('student.invoices')->with('status', 'Payment was cancelled. No payment was recorded.');
    }

    private function authorizeStudentTransaction(PayHereTransaction $transaction): void
    {
        $student = auth()->user()->student;
        abort_unless($student && $transaction->invoice->student_id === $student->id, 404);
    }
}
