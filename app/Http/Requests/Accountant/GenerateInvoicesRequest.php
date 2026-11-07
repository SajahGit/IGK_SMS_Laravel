<?php

namespace App\Http\Requests\Accountant;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->can('invoices.create'));
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'exists:classes,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'fee_structure_ids' => ['required', 'array', 'min:1'],
            'fee_structure_ids.*' => ['exists:fee_structures,id'],
            'fee_item_ids' => ['nullable', 'array'],
            'fee_item_ids.*' => ['integer', 'exists:fee_items,id'],
            'as_draft' => ['nullable', 'boolean'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
        ];
    }
}
