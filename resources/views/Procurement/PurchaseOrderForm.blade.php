@extends('Layouts.LayoutApp')
@section('title', $order ? 'Edit Purchase Order' : 'New Purchase Order')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="mb-0">{{ $order ? 'Edit Draft Purchase Order' : 'New Purchase Order' }}</h3><a href="{{ $order ? route('procurement.orders.show', $order) : route('procurement.orders.index') }}" class="btn btn-outline-secondary">Back</a></div>
@include('Layouts.LayoutErrors')
@if ($suppliers->isEmpty() || $inventoryItems->isEmpty())
<div class="alert alert-warning">Create at least one active supplier and one active inventory item before preparing an order.</div>
@endif
<div class="card shadow-sm border-0"><div class="card-body">
    <form method="POST" action="{{ $order ? route('procurement.orders.update', $order) : route('procurement.orders.store') }}">
        @csrf @if ($order) @method('PUT') @endif
        <div class="row g-3 mb-4">
            <div class="col-md-6"><label class="form-label">Supplier <span class="text-danger">*</span></label><select name="supplier_id" class="form-select" required><option value="">Select supplier</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $order?->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Order date <span class="text-danger">*</span></label><input type="date" name="order_date" class="form-control" required value="{{ old('order_date', $order?->order_date?->toDateString() ?? today()->toDateString()) }}"></div>
            <div class="col-md-3"><label class="form-label">Expected date</label><input type="date" name="expected_date" class="form-control" value="{{ old('expected_date', $order?->expected_date?->toDateString()) }}"></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control" maxlength="5000">{{ old('notes', $order?->notes) }}</textarea></div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Order Lines</h5><button type="button" class="btn btn-sm btn-outline-primary" id="add-order-line">Add Line</button></div>
        <div id="order-lines">
            @php
                $existingLines = old('items', $order?->items?->map(fn ($line) => ['inventory_item_id' => $line->inventory_item_id, 'quantity_ordered' => $line->quantity_ordered, 'unit_cost' => $line->unit_cost])->all() ?? [['inventory_item_id' => '', 'quantity_ordered' => 1, 'unit_cost' => '0.00']]);
            @endphp
            @foreach ($existingLines as $index => $line)
            <div class="row g-2 align-items-end mb-2 order-line">
                <div class="col-md-6"><label class="form-label">Inventory item</label><select name="items[{{ $index }}][inventory_item_id]" class="form-select" required><option value="">Select item</option>@foreach ($inventoryItems as $item)<option value="{{ $item->id }}" @selected((string) ($line['inventory_item_id'] ?? '') === (string) $item->id)>{{ $item->sku }} — {{ $item->name }} (on hand: {{ $item->quantity_on_hand }} {{ $item->unit }})</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label">Quantity</label><input type="number" name="items[{{ $index }}][quantity_ordered]" min="1" max="1000000" class="form-control" required value="{{ $line['quantity_ordered'] ?? 1 }}"></div>
                <div class="col-md-3"><label class="form-label">Unit cost</label><input type="number" name="items[{{ $index }}][unit_cost]" min="0" max="9999999999.99" step="0.01" class="form-control" required value="{{ $line['unit_cost'] ?? '0.00' }}"></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-order-line" aria-label="Remove line"><i class="bi bi-x-lg"></i></button></div>
            </div>
            @endforeach
        </div>
        <div class="mt-4"><button class="btn btn-primary">Save Draft</button> <a href="{{ $order ? route('procurement.orders.show', $order) : route('procurement.orders.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</div></div>
<template id="order-line-template"><div class="row g-2 align-items-end mb-2 order-line">
    <div class="col-md-6"><label class="form-label">Inventory item</label><select name="items[__INDEX__][inventory_item_id]" class="form-select" required><option value="">Select item</option>@foreach ($inventoryItems as $item)<option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->name }} (on hand: {{ $item->quantity_on_hand }} {{ $item->unit }})</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label">Quantity</label><input type="number" name="items[__INDEX__][quantity_ordered]" min="1" max="1000000" class="form-control" required value="1"></div>
    <div class="col-md-3"><label class="form-label">Unit cost</label><input type="number" name="items[__INDEX__][unit_cost]" min="0" max="9999999999.99" step="0.01" class="form-control" required value="0.00"></div>
    <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-order-line" aria-label="Remove line"><i class="bi bi-x-lg"></i></button></div>
</div></template>
<script>
(() => {
    let nextIndex = {{ count($existingLines) }};
    const lines = document.getElementById('order-lines');
    document.getElementById('add-order-line').addEventListener('click', () => {
        const row = document.getElementById('order-line-template').content.cloneNode(true);
        row.querySelectorAll('[name]').forEach((input) => input.name = input.name.replace('__INDEX__', nextIndex));
        nextIndex += 1;
        lines.appendChild(row);
    });
    lines.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-order-line');
        if (button && lines.querySelectorAll('.order-line').length > 1) button.closest('.order-line').remove();
    });
})();
</script>
@endsection
