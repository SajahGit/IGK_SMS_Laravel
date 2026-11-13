<?php

namespace App\Http\Requests\Procurement;

use App\Rules\ValidInternationalPhone;
use App\Rules\ValidPersonName;
use App\Support\InternationalPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        InternationalPhoneNumber::normalizeRequest($this);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:150', new ValidPersonName],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($this->route('supplier'))],
            'phone' => ['nullable', 'string', 'max:16', Rule::unique('suppliers', 'phone')->ignore($this->route('supplier')), new ValidInternationalPhone($this->input('phone_region'))],
            'address' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
