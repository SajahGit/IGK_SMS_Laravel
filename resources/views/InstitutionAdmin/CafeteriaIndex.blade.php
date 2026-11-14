@extends('Layouts.LayoutApp')
@section('title', 'Cafeteria')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Cafeteria</h3><small class="text-muted">Manage stock-linked menu items and student orders.</small></div>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="alert alert-info">Placing an order reserves stock. Fulfillment records handover; cafeteria orders do not record payment or replace a finance receipt.</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><h5 class="mb-0">Add menu item</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.cafeteria.items.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3"><label for="new-inventory-item" class="form-label">Stock item</label><select id="new-inventory-item" name="inventory_item_id" class="form-select" required><option value="">Choose stock</option>@foreach ($inventoryItems as $inventoryItem)<option value="{{ $inventoryItem->id }}">{{ $inventoryItem->name }} ({{ $inventoryItem->quantity_on_hand }} {{ $inventoryItem->unit }})</option>@endforeach</select></div>
            <div class="col-md-3"><label for="new-name" class="form-label">Menu name</label><input id="new-name" name="name" class="form-control" maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x2d]*" data-live-filter="letter-start-name" required></div>
            <div class="col-md-2"><label for="new-price" class="form-label">Price</label><input id="new-price" name="price" type="number" step="0.01" min="0.01" class="form-control" required></div>
            <div class="col-md-3"><label for="new-description" class="form-label">Description</label><input id="new-description" name="description" class="form-control" maxlength="255" pattern="[A-Za-z0-9\s.,!?\x22\x27\x28\x29\x2d\x2f:]*" data-live-filter="description"></div>
            <div class="col-md-1"><button class="btn btn-primary">Add</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><h5 class="mb-0">Menu items</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Item</th><th>Stock</th><th>Unit price</th><th>Availability</th><th>Update</th></tr></thead>
            <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->name }}<div class="small text-muted">{{ $item->description }}</div></td>
                    <td>{{ $item->inventoryItem->quantity_on_hand }} {{ $item->inventoryItem->unit }}</td>
                    <td>{{ number_format((float) $item->price, 2) }}</td>
                    <td><span class="badge {{ $item->is_available ? 'bg-success' : 'bg-secondary' }}">{{ $item->is_available ? 'Available' : 'Unavailable' }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.cafeteria.items.update', $item) }}" class="d-flex flex-wrap gap-2">
                            @csrf @method('PUT')
                            <select name="inventory_item_id" class="form-select form-select-sm" aria-label="Stock item" required>
                                @foreach ($inventoryItems as $inventoryItem)
                                    <option value="{{ $inventoryItem->id }}" @selected($item->inventory_item_id === $inventoryItem->id)>{{ $inventoryItem->name }}</option>
                                @endforeach
                                @if (! $inventoryItems->contains('id', $item->inventory_item_id))
                                    <option value="{{ $item->inventory_item_id }}" selected>{{ $item->inventoryItem->name }} (inactive stock)</option>
                                @endif
                            </select>
                            <input name="name" value="{{ $item->name }}" class="form-control form-control-sm" aria-label="Menu name" maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x2d]*" data-live-filter="letter-start-name" required>
                            <input name="price" value="{{ $item->price }}" type="number" min="0.01" step="0.01" class="form-control form-control-sm" aria-label="Price" required>
                            <input name="description" value="{{ $item->description }}" class="form-control form-control-sm" aria-label="Description" maxlength="255" pattern="[A-Za-z0-9\s.,!?\x22\x27\x28\x29\x2d\x2f:]*" data-live-filter="description">
                            <select name="is_available" class="form-select form-select-sm" aria-label="Availability">
                                <option value="1" @selected($item->is_available)>Available</option>
                                <option value="0" @selected(! $item->is_available)>Unavailable</option>
                            </select>
                            <button class="btn btn-sm btn-outline-primary">Save</button>
                        </form>
                        <form method="POST" action="{{ route('admin.cafeteria.items.destroy', $item) }}" class="mt-1" onsubmit="return confirm('Delete this menu item only when it has no order history?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete unused item</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No menu items have been added.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent"><h5 class="mb-0">Orders</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Student</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $order->student?->user?->name ?? 'Unknown student' }}<div class="small text-muted">{{ $order->student?->admission_no ?? '—' }}</div></td>
                    <td>{{ $order->order_date->format('d M Y') }}</td>
                    <td>@foreach ($order->items as $line)<div>{{ $line->cafeteriaItem?->name ?? 'Unavailable item' }} × {{ $line->quantity }}</div>@endforeach</td>
                    <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                    <td><span class="badge {{ $order->status === 'fulfilled' ? 'bg-success' : ($order->status === 'reserved' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ ucfirst($order->status) }}</span></td>
                    <td>
                        @if ($order->status === 'reserved')
                            <form method="POST" action="{{ route('admin.cafeteria.orders.fulfill', $order) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Fulfill</button></form>
                            <form method="POST" action="{{ route('admin.cafeteria.orders.cancel', $order) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No cafeteria orders have been placed.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>
@endsection
