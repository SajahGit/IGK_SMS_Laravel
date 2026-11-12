@extends('Layouts.LayoutApp')
@section('title', 'Purchase Orders')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Purchase Orders</h3><small class="text-muted">Draft, review, approve, and receive stock orders.</small></div>
    <div class="d-flex gap-2">
        @can('suppliers.read')<a href="{{ route('procurement.suppliers.index') }}" class="btn btn-outline-secondary">Suppliers</a>@endcan
        @can('procurement.create')<a href="{{ route('procurement.orders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Purchase Order</a>@endcan
    </div>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card shadow-sm border-0 mb-3"><div class="card-body"><form method="GET" class="row g-2">
    <div class="col-md-5"><input name="search" class="form-control" placeholder="Search order number" value="{{ request('search') }}"></div>
    <div class="col-md-4"><select name="status" class="form-select"><option value="">All statuses</option>@foreach (['draft','submitted','approved','partially_received','received','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
    <div class="col-md-3"><button class="btn btn-outline-primary">Filter</button> <a class="btn btn-outline-secondary" href="{{ route('procurement.orders.index') }}">Clear</a></div>
</form></div></div>
<div class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
    <thead class="table-light"><tr><th>Order</th><th>Supplier</th><th>Order date</th><th>Expected</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
    @forelse ($orders as $order)
        <tr><td><a href="{{ route('procurement.orders.show', $order) }}">{{ $order->order_no }}</a></td><td>{{ $order->supplier->name }}</td><td>{{ $order->order_date->format('Y-m-d') }}</td><td>{{ $order->expected_date?->format('Y-m-d') ?: '—' }}</td><td><span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $order->status)) }}</span></td><td class="text-end"><a href="{{ route('procurement.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a></td></tr>
    @empty<tr><td colspan="6" class="text-center text-muted py-4">No purchase orders found.</td></tr>@endforelse
    </tbody></table></div></div>
<div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>
@endsection
