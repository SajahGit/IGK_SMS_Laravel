@extends('Layouts.LayoutApp')
@section('title', $order->order_no)
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">{{ $order->order_no }}</h3><small class="text-muted">{{ $order->supplier->name }} · {{ $order->order_date->format('Y-m-d') }}</small></div>
    <div class="d-flex gap-2">
        <a href="{{ route('procurement.orders.index') }}" class="btn btn-outline-secondary">Back</a>
        @if ($order->status === 'draft')
            @can('procurement.update')<a href="{{ route('procurement.orders.edit', $order) }}" class="btn btn-outline-primary">Edit</a>@endcan
            @can('procurement.delete')<form method="POST" action="{{ route('procurement.orders.destroy', $order) }}" onsubmit="return confirm('Delete this draft purchase order?')">@csrf @method('DELETE')<button class="btn btn-outline-danger">Delete</button></form>@endcan
        @endif
    </div>
</div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card shadow-sm border-0 mb-3"><div class="card-body row g-3">
    <div class="col-md-3"><div class="text-muted small">Status</div><strong>{{ ucwords(str_replace('_', ' ', $order->status)) }}</strong></div>
    <div class="col-md-3"><div class="text-muted small">Expected date</div><strong>{{ $order->expected_date?->format('Y-m-d') ?: '—' }}</strong></div>
    <div class="col-md-3"><div class="text-muted small">Created by</div><strong>{{ $order->creator?->name ?: '—' }}</strong></div>
    <div class="col-md-3"><div class="text-muted small">Approved by</div><strong>{{ $order->approver?->name ?: '—' }}</strong></div>
</div></div>
<div class="card shadow-sm border-0 mb-3"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
    <thead class="table-light"><tr><th>Inventory item</th><th>Ordered</th><th>Received</th><th>Remaining</th><th>Unit cost</th><th>Line total</th></tr></thead><tbody>
    @foreach ($order->items as $line)
        <tr><td>{{ $line->inventoryItem->sku }} — {{ $line->inventoryItem->name }}</td><td>{{ $line->quantity_ordered }} {{ $line->inventoryItem->unit }}</td><td>{{ $line->quantity_received }}</td><td>{{ $line->quantity_ordered - $line->quantity_received }}</td><td>{{ \App\Support\Money::format($line->unit_cost) }}</td><td>{{ \App\Support\Money::format(\App\Support\Money::mul($line->unit_cost, $line->quantity_ordered)) }}</td></tr>
    @endforeach
    <tr><th colspan="5" class="text-end">Order total</th><th>{{ \App\Support\Money::format($total) }}</th></tr>
    </tbody></table></div></div>
@if ($order->notes)<div class="card shadow-sm border-0 mb-3"><div class="card-body"><strong>Notes</strong><p class="mb-0 mt-2">{{ $order->notes }}</p></div></div>@endif

<div class="d-flex flex-wrap gap-2 mb-3">
    @if ($order->status === 'draft')
        @can('procurement.update')
        <form method="POST" action="{{ route('procurement.orders.submit', $order) }}">@csrf<button class="btn btn-primary">Submit for Approval</button></form>
        @endcan
    @endif
    @if ($order->status === 'submitted')
        @can('procurement.approve')
        <form method="POST" action="{{ route('procurement.orders.approve', $order) }}">@csrf<button class="btn btn-success">Approve Order</button></form>
        @endcan
    @endif
    @if (in_array($order->status, ['draft', 'submitted', 'approved'], true))
        @can('procurement.update')
        <form method="POST" action="{{ route('procurement.orders.cancel', $order) }}" class="d-flex gap-2">@csrf<input name="reason" minlength="5" maxlength="1000" class="form-control" required placeholder="Reason for cancellation"><button class="btn btn-outline-danger">Cancel Order</button></form>
        @endcan
    @endif
</div>

@if (in_array($order->status, ['approved', 'partially_received'], true))
@can('procurement.update')
<div class="card shadow-sm border-0"><div class="card-body">
    <h5>Receive Stock</h5><p class="text-muted small">Only the quantities entered here are added to stock. Repeat for partial deliveries.</p>
    <form method="POST" action="{{ route('procurement.orders.receive', $order) }}">@csrf
        @foreach ($order->items as $line)
            @php($remaining = $line->quantity_ordered - $line->quantity_received)
            @if ($remaining > 0)
            <div class="row g-2 align-items-center mb-2"><div class="col-md-7">{{ $line->inventoryItem->name }} ({{ $remaining }} {{ $line->inventoryItem->unit }} remaining)</div><div class="col-md-5"><input type="number" name="items[{{ $line->id }}]" min="0" max="{{ $remaining }}" value="0" class="form-control" aria-label="Received quantity for {{ $line->inventoryItem->name }}"></div></div>
            @endif
        @endforeach
        <div class="row g-2 mt-2"><div class="col-md-4"><input name="reference" maxlength="100" class="form-control" placeholder="Delivery note reference"></div><div class="col-md-6"><input name="notes" maxlength="5000" class="form-control" placeholder="Receiving notes"></div><div class="col-md-2"><button class="btn btn-primary w-100">Record Receipt</button></div></div>
    </form>
</div></div>
@endcan
@endif
@endsection
