<?php

namespace App\Http\Requests\Accountant;

use Illuminate\Foundation\Http\FormRequest;

class ReversePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // SOP PR-5: only a Super Admin may reverse a payment.
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'reversal_reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }
}
