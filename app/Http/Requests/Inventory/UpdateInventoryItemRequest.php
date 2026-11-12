<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\BranchContext;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->can('inventory.update'));
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:50', 'alpha_dash', $this->uniqueSkuRule()],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:30'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function uniqueSkuRule(): \Illuminate\Validation\Rules\Unique
    {
        $branchId = BranchContext::activeBranchId($this->user());
        $rule = Rule::unique('inventory_items', 'sku')->ignore($this->route('inventoryItem'));

        return $branchId === null
            ? $rule->whereNull('branch_id')
            : $rule->where('branch_id', $branchId);
    }
}
