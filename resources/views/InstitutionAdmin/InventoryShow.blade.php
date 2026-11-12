@extends('Layouts.LayoutApp')
@section('title', $inventoryItem->name)
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">{{ $inventoryItem->name }}</h3><small class="text-muted">{{ $inventoryItem->sku }} · {{ $inventoryItem->category }}</small></div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">Back</a>
        @can('inventory.update')<a href="{{ route('admin.inventory.edit', $inventoryItem) }}" class="btn btn-outline-primary">Edit</a>@endcan
        @can('inventory.delete')
        <form method="POST" action="{{ route('admin.inventory.destroy', $inventoryItem) }}" onsubmit="return confirm('Delete this inventory item?')">@csrf @method('DELETE')<button class="btn btn-outline-danger">Delete</button></form>
        @endcan
    </div>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">Quantity on hand</div><div class="fs-4 fw-semibold">{{ number_format($inventoryItem->quantity_on_hand) }} {{ $inventoryItem->unit }}</div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">Reorder level</div><div class="fs-4 fw-semibold">{{ number_format($inventoryItem->reorder_level) }} {{ $inventoryItem->unit }}</div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">Status</div><div class="fs-4 fw-semibold">{{ $inventoryItem->is_active ? ($inventoryItem->isLowStock() ? 'Low stock' : 'Active') : 'Inactive' }}</div></div></div></div>
</div>

@can('inventory_movements.create')
<div class="card shadow-sm border-0 mb-3"><div class="card-body">
    <h5>Record Stock Movement</h5>
    <form method="POST" action="{{ route('admin.inventory.movements.store', $inventoryItem) }}">
        @csrf
        <div class="row g-2">
            <div class="col-md-3"><label class="form-label">Movement</label><select name="type" class="form-select" required><option value="receipt">Receipt (add stock)</option><option value="issue">Issue (remove stock)</option><option value="adjustment">Adjustment (set balance)</option></select></div>
            <div class="col-md-2"><label class="form-label">Quantity</label><input type="number" name="quantity" min="1" max="1000000" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Reference</label><input name="reference" maxlength="100" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Notes</label><input name="notes" maxlength="5000" class="form-control"></div>
        </div>
        <button class="btn btn-primary mt-3">Record Movement</button>
    </form>
</div></div>
@endcan

<div class="card shadow-sm border-0"><div class="card-header bg-transparent"><strong>Stock Movement History</strong></div><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle"><thead class="table-light"><tr><th>Date</th><th>Type</th><th>Change</th><th>Balance after</th><th>Reference</th><th>Recorded by</th><th>Notes</th></tr></thead><tbody>
    @forelse ($movements as $movement)
        <tr><td>{{ $movement->created_at->format('Y-m-d H:i') }}</td><td>{{ ucfirst($movement->type) }}</td><td>{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }} {{ $inventoryItem->unit }}</td><td>{{ $movement->balance_after }} {{ $inventoryItem->unit }}</td><td>{{ $movement->reference ?: '—' }}</td><td>{{ $movement->recorder?->name ?: 'System' }}</td><td>{{ $movement->notes ?: '—' }}</td></tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">No stock movements recorded.</td></tr>
    @endforelse
    </tbody></table>
</div></div>
<div class="mt-3">{{ $movements->links('pagination::bootstrap-5') }}</div>
@endsection
