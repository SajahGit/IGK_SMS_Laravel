<?php

namespace App\Support;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdf
{
    public static function download(Invoice $invoice): Response
    {
        $invoice->load(['student.user', 'student.class', 'student.guardian.user', 'term', 'items', 'payments.receiver']);

        return Pdf::setOptions(['isRemoteEnabled' => false])
            ->loadView('Pdf.PdfInvoice', [
                'invoice' => $invoice,
                'institution' => InstitutionDetails::values(),
            ])
            ->setPaper('a4')
            ->download('invoice-'.$invoice->invoice_no.'.pdf');
    }
}
