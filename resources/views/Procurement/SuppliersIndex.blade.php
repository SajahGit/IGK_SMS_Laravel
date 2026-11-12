@extends('Layouts.LayoutApp')
@section('title', 'Suppliers')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Suppliers</h3><small class="text-muted">Manage purchasing contacts.</small></div>
    @can('suppliers.create')<a href="{{ route('procurement.suppliers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Supplier</a>@endcan
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="d-flex gap-2 mb-3"><a href="{{ route('procurement.orders.index') }}" class="btn btn-outline-secondary">Purchase Orders</a></div>
<div class="card shadow-sm border-0"><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle"><thead class="table-light"><tr><th>Supplier</th><th>Contact</th><th>Email</th><th>Phone</th><th>Orders</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
    @forelse ($suppliers as $supplier)
        <tr><td>{{ $supplier->name }}</td><td>{{ $supplier->contact_name ?: '—' }}</td><td>{{ $supplier->email ?: '—' }}</td><td>{{ $supplier->phone ?: '—' }}</td><td>{{ $supplier->purchase_orders_count }}</td><td>{{ $supplier->is_active ? 'Active' : 'Inactive' }}</td>
            <td class="text-end">
                @can('suppliers.update')<a class="btn btn-sm btn-outline-primary" href="{{ route('procurement.suppliers.edit', $supplier) }}">Edit</a>@endcan
                @can('suppliers.delete')
                @if ($supplier->purchase_orders_count === 0)
                <form method="POST" class="d-inline" action="{{ route('procurement.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete supplier {{ $supplier->name }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                @endif
                @endcan
            </td></tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">No suppliers defined.</td></tr>@endforelse
    </tbody></table>
</div></div>
<div class="mt-3">{{ $suppliers->links('pagination::bootstrap-5') }}</div>
@endsection
