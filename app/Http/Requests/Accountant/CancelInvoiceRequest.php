<?php

namespace App\Http\Requests\Accountant;

use Illuminate\Foundation\Http\FormRequest;

class CancelInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can('invoices.update'));
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }
}
