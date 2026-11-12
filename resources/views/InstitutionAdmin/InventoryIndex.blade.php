@extends('Layouts.LayoutApp')
@section('title', 'Inventory')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Inventory</h3>
        <small class="text-muted">Track stock on hand, low-stock items, and stock movement history.</small>
    </div>
    @can('inventory.create')
    <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Item</a>
    @endcan
</div>

@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-2 align-items-center">
            <div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search SKU, item, or category"></div>
            <div class="col-md-3"><label class="form-check"><input class="form-check-input" type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock'))> Low stock only</label></div>
            <div class="col-md-3"><button class="btn btn-outline-primary">Filter</button> <a class="btn btn-outline-secondary" href="{{ route('admin.inventory.index') }}">Clear</a></div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>SKU</th><th>Item</th><th>Category</th><th>On hand</th><th>Reorder level</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->sku }}</td>
                    <td><a href="{{ route('admin.inventory.show', $item) }}">{{ $item->name }}</a></td>
                    <td>{{ $item->category }}</td>
                    <td>{{ number_format($item->quantity_on_hand) }} {{ $item->unit }}</td>
                    <td>{{ number_format($item->reorder_level) }} {{ $item->unit }}</td>
                    <td>
                        @if (!$item->is_active)<span class="badge bg-secondary">Inactive</span>
                        @elseif ($item->isLowStock())<span class="badge bg-warning text-dark">Low stock</span>
                        @else<span class="badge bg-success">In stock</span>@endif
                    </td>
                    <td class="text-end"><a href="{{ route('admin.inventory.show', $item) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No inventory items found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $items->links('pagination::bootstrap-5') }}</div>
@endsection
