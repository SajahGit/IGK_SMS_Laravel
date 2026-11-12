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
            'sku' => ['required', 'string', 'max:50', 'alpha_dash', $this->uniqueSkuRule()],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:30'],
            'initial_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
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
