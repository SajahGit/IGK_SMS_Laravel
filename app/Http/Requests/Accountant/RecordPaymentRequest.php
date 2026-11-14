<?php

namespace App\Http\Requests\Accountant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('payments.create'));
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', 'decimal:0,2'],
            'method' => ['required', 'string', Rule::in(['cash', 'bank_transfer', 'cheque'])],
            'reference' => ['nullable', 'string', 'max:40', 'regex:/\A[A-Z0-9\-\/]*\z/'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $reference = $this->input('reference');
        if (is_string($reference)) {
            $this->merge(['reference' => strtoupper($reference)]);
        }
    }
}
