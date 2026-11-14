<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\BranchContext;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->can('inventory.create'));
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:30', 'regex:/\A[A-Z0-9-]+\z/', $this->uniqueSkuRule()],
            'name' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z][A-Za-z0-9\s()\-]*\z/'],
            'category' => ['required', 'string', 'max:40', 'regex:/\A[A-Za-z\s-]+\z/'],
            'unit' => ['required', 'string', 'max:40', 'regex:/\A[A-Za-z\s-]+\z/'],
            'initial_quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'reference' => ['nullable', 'string', 'max:40', 'regex:/\A[A-Z0-9-]*\z/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['sku', 'reference'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $this->merge([$field => strtoupper($value)]);
            }
        }
    }

    private function uniqueSkuRule(): \Illuminate\Validation\Rules\Unique
    {
        $branchId = BranchContext::activeBranchId($this->user());
        $rule = Rule::unique('inventory_items', 'sku');

        return $branchId === null
            ? $rule->whereNull('branch_id')
            : $rule->where('branch_id', $branchId);
    }
}
