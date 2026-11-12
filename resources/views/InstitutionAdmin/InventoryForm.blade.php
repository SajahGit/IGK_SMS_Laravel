@extends('Layouts.LayoutApp')
@section('title', $inventoryItem ? 'Edit Inventory Item' : 'Add Inventory Item')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{{ $inventoryItem ? 'Edit Inventory Item' : 'Add Inventory Item' }}</h3>
    <a href="{{ $inventoryItem ? route('admin.inventory.show', $inventoryItem) : route('admin.inventory.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm border-0"><div class="card-body">
    <form method="POST" action="{{ $inventoryItem ? route('admin.inventory.update', $inventoryItem) : route('admin.inventory.store') }}">
        @csrf
        @if ($inventoryItem) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">SKU <span class="text-danger">*</span></label><input name="sku" class="form-control" required maxlength="50" value="{{ old('sku', $inventoryItem?->sku) }}"></div>
            <div class="col-md-8"><label class="form-label">Item name <span class="text-danger">*</span></label><input name="name" class="form-control" required maxlength="150" value="{{ old('name', $inventoryItem?->name) }}"></div>
            <div class="col-md-4"><label class="form-label">Category <span class="text-danger">*</span></label><input name="category" class="form-control" required maxlength="100" value="{{ old('category', $inventoryItem?->category) }}"></div>
            <div class="col-md-4"><label class="form-label">Unit <span class="text-danger">*</span></label><input name="unit" class="form-control" required maxlength="30" placeholder="each, box, litre" value="{{ old('unit', $inventoryItem?->unit) }}"></div>
            @if (!$inventoryItem)
            <div class="col-md-4"><label class="form-label">Opening quantity <span class="text-danger">*</span></label><input type="number" min="0" max="1000000" name="initial_quantity" class="form-control" required value="{{ old('initial_quantity', 0) }}"></div>
            <div class="col-md-4"><label class="form-label">Opening stock reference</label><input name="reference" class="form-control" maxlength="100" value="{{ old('reference') }}"></div>
            @else
            <div class="col-md-4"><label class="form-label">Quantity on hand</label><input class="form-control" value="{{ number_format($inventoryItem->quantity_on_hand).' '.$inventoryItem->unit }}" disabled><div class="form-text">Stock is changed using a recorded movement.</div></div>
            @endif
            <div class="col-md-4"><label class="form-label">Reorder level <span class="text-danger">*</span></label><input type="number" min="0" max="1000000" name="reorder_level" class="form-control" required value="{{ old('reorder_level', $inventoryItem?->reorder_level ?? 0) }}"></div>
            <div class="col-md-8"><label class="form-label">Description</label><textarea name="description" rows="3" maxlength="5000" class="form-control">{{ old('description', $inventoryItem?->description) }}</textarea></div>
            <div class="col-12"><label class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $inventoryItem?->is_active ?? true))> Active item</label></div>
        </div>
        <div class="mt-4"><button class="btn btn-primary">{{ $inventoryItem ? 'Save Changes' : 'Create Item' }}</button> <a href="{{ $inventoryItem ? route('admin.inventory.show', $inventoryItem) : route('admin.inventory.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</div></div>
@endsection
