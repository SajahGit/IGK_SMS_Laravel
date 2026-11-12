<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->can('inventory_movements.create'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['receipt', 'issue', 'adjustment'])],
            'quantity' => [
                'required',
                'integer',
                Rule::when($this->input('type') === 'adjustment', ['min:0'], ['min:1']),
                'max:1000000',
            ],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
