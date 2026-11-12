<?php

namespace App\Http\Requests\Procurement;

class UpdatePurchaseOrderRequest extends StorePurchaseOrderRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('procurement.update') ?? false;
    }
}
