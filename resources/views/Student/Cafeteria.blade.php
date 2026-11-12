@extends('Layouts.LayoutApp')
@section('title', 'Cafeteria')
@section('content')
<h3 class="mb-3">Cafeteria</h3>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="alert alert-info">Submitting an order reserves the listed stock. This page does not collect payment or issue a receipt.</div>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-transparent"><h5 class="mb-0">Available menu</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('student.cafeteria.orders.store') }}">
            @csrf
            <div class="mb-3"><label for="order_date" class="form-label">Order date</label><input id="order_date" name="order_date" type="date" min="{{ today()->toDateString() }}" value="{{ old('order_date', today()->toDateString()) }}" class="form-control" required></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>Item</th><th>Price</th><th>Available stock</th><th>Quantity</th></tr></thead>
                    <tbody>
                    @forelse ($items as $index => $item)
                        <tr>
                            <td>{{ $item->name }}<div class="small text-muted">{{ $item->description }}</div></td>
                            <td>{{ number_format((float) $item->price, 2) }}</td>
                            <td>{{ $item->inventoryItem->quantity_on_hand }} {{ $item->inventoryItem->unit }}</td>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][cafeteria_item_id]" value="{{ $item->id }}">
                                <input type="number" name="items[{{ $index }}][quantity]" min="0" max="100" value="0" class="form-control" aria-label="Quantity of {{ $item->name }}">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No menu items are currently available.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @error('items')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
            @if ($items->isNotEmpty())<button class="btn btn-primary">Place order</button>@endif
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-transparent"><h5 class="mb-0">My orders</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $order->order_date->format('d M Y') }}</td>
                    <td>@foreach ($order->items as $line)<div>{{ $line->cafeteriaItem->name }} × {{ $line->quantity }}</div>@endforeach</td>
                    <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td>@if ($order->status === 'reserved')<form method="POST" action="{{ route('student.cafeteria.orders.cancel', $order) }}">@csrf<button class="btn btn-sm btn-outline-danger">Cancel order</button></form>@endif</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">You have no cafeteria orders.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>
@endsection
