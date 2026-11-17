@extends('Layouts.LayoutApp')
@section('title', 'Cafeteria')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Cafeteria Management</h3>
        <small class="text-muted">Manage stock-linked menu items, multi-campus availability, fresh stock additions, and student orders.</small>
    </div>
</div>
@include('Layouts.LayoutErrors')
<div class="alert alert-info d-flex align-items-center gap-2">
    <i class="bi bi-info-circle-fill fs-5"></i>
    <div>Placing an order reserves real-time inventory stock across campus branches. Fulfillment records handover; cafeteria orders do not replace official finance fee receipts.</div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent pb-0">
        <ul class="nav nav-tabs card-header-tabs" id="cafeteriaStockTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-existing-btn" data-bs-toggle="tab" data-bs-target="#tab-existing" type="button" role="tab" aria-selected="true">
                    <i class="bi bi-link-45deg me-1"></i>Link Existing Stock Item
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-success fw-semibold" id="tab-fresh-btn" data-bs-toggle="tab" data-bs-target="#tab-fresh" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-plus-circle-fill me-1"></i>Create Fresh Stock & Menu Item
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-primary" id="tab-quick-stock-btn" data-bs-toggle="tab" data-bs-target="#tab-quick-stock" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-box-seam me-1"></i>Add Inventory Stock Only
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-4">
        <div class="tab-content" id="cafeteriaStockTabContent">
            {{-- TAB 1: Link Existing Stock --}}
            <div class="tab-pane fade show active" id="tab-existing" role="tabpanel">
                <form method="POST" action="{{ route('admin.cafeteria.items.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label for="new-inventory-item" class="form-label fw-semibold">Select Stock Item <span class="text-danger">*</span></label>
                        <select id="new-inventory-item" name="inventory_item_id" class="form-select" required>
                            <option value="">Choose stock item (Any Campus)...</option>
                            <optgroup label="Available Stock Items">
                                @foreach ($inventoryItems as $inventoryItem)
                                    @if (! in_array($inventoryItem->id, $linkedInventoryItemIds, true))
                                        <option value="{{ $inventoryItem->id }}">
                                            {{ $inventoryItem->name }} ({{ $inventoryItem->quantity_on_hand }} {{ $inventoryItem->unit }}) — {{ $inventoryItem->branch ? $inventoryItem->branch->name : 'Campus-Wide Stock' }}
                                        </option>
                                    @endif
                                @endforeach
                            </optgroup>
                            @php
                                $alreadyLinked = $inventoryItems->filter(fn($i) => in_array($i->id, $linkedInventoryItemIds, true));
                            @endphp
                            @if ($alreadyLinked->isNotEmpty())
                                <optgroup label="Already Linked to Another Menu Item (Disabled)">
                                    @foreach ($alreadyLinked as $inventoryItem)
                                        <option value="{{ $inventoryItem->id }}" disabled>
                                            {{ $inventoryItem->name }} ({{ $inventoryItem->quantity_on_hand }} {{ $inventoryItem->unit }}) — Already linked
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <div class="form-text small text-muted">Stock can come from any campus branch or campus-wide inventory.</div>
                    </div>
                    <div class="col-md-3">
                        <label for="new-name" class="form-label fw-semibold">Menu Item Name <span class="text-danger">*</span></label>
                        <input id="new-name" name="name" class="form-control" maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x2d]*" data-live-filter="letter-start-name" placeholder="e.g. Chicken Sandwich" required>
                    </div>
                    <div class="col-md-2">
                        <label for="new-price" class="form-label fw-semibold">Price (LKR) <span class="text-danger">*</span></label>
                        <input id="new-price" name="price" type="number" step="0.01" min="0.01" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="col-md-3">
                        <label for="new-branch" class="form-label fw-semibold">Campus Availability</label>
                        <select id="new-branch" name="branch_id" class="form-select">
                            <option value="">All Campuses (Campus-Wide)</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(session('active_branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-10">
                        <label for="new-description" class="form-label">Description (Optional)</label>
                        <input id="new-description" name="description" class="form-control" maxlength="255" pattern="[A-Za-z0-9\s.,!?\x22\x27\x28\x29\x2d\x2f:]*" data-live-filter="description" placeholder="Brief notes, ingredients, dietary details">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg me-1"></i>Add Menu Item
                        </button>
                    </div>
                </form>
            </div>

            {{-- TAB 2: Create Fresh Stock & Menu Item (All-in-One) --}}
            <div class="tab-pane fade" id="tab-fresh" role="tabpanel">
                <form method="POST" action="{{ route('admin.cafeteria.items.store') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="stock_source" value="fresh">
                    <div class="col-12">
                        <div class="alert alert-success py-2 mb-0 small">
                            <i class="bi bi-lightning-charge-fill me-1"></i><strong>Instant Fresh Stock Creation:</strong> This adds a fresh inventory stock record and simultaneously publishes it as an active cafeteria menu item in a single step!
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="fresh-name" class="form-label fw-semibold">Item & Fresh Stock Name <span class="text-danger">*</span></label>
                        <input id="fresh-name" name="name" class="form-control" maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x2d]*" data-live-filter="letter-start-name" placeholder="e.g. Fresh Chicken Pastry / Milk Tea" required>
                    </div>
                    <div class="col-md-2">
                        <label for="fresh-unit" class="form-label fw-semibold">Unit <span class="text-danger">*</span></label>
                        <select id="fresh-unit" name="unit" class="form-select" required>
                            <option value="portion" selected>portion</option>
                            <option value="piece">piece</option>
                            <option value="cup">cup</option>
                            <option value="plate">plate</option>
                            <option value="pack">pack</option>
                            <option value="bottle">bottle</option>
                            <option value="can">can</option>
                            <option value="box">box</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fresh-quantity" class="form-label fw-semibold">Fresh Stock Qty <span class="text-danger">*</span></label>
                        <input id="fresh-quantity" name="fresh_stock_quantity" type="number" min="0" max="100000" class="form-control" value="50" required>
                    </div>
                    <div class="col-md-2">
                        <label for="fresh-price" class="form-label fw-semibold">Price (LKR) <span class="text-danger">*</span></label>
                        <input id="fresh-price" name="price" type="number" step="0.01" min="0.01" class="form-control" placeholder="150.00" required>
                    </div>
                    <div class="col-md-2">
                        <label for="fresh-branch" class="form-label fw-semibold">Campus Branch</label>
                        <select id="fresh-branch" name="branch_id" class="form-select">
                            <option value="">All Campuses (Campus-Wide)</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(session('active_branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fresh-category" class="form-label">Category</label>
                        <select id="fresh-category" name="category" class="form-select">
                            <option value="Cafeteria" selected>Cafeteria</option>
                            <option value="Bakery">Bakery & Pastries</option>
                            <option value="Beverage">Beverages & Juices</option>
                            <option value="Meals">Hot Meals & Rice</option>
                            <option value="Snacks">Snacks & Confectionery</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fresh-reorder" class="form-label">Low Stock Alert Level</label>
                        <input id="fresh-reorder" name="reorder_level" type="number" min="0" class="form-control" value="5">
                    </div>
                    <div class="col-md-5">
                        <label for="fresh-desc" class="form-label">Description (Optional)</label>
                        <input id="fresh-desc" name="description" class="form-control" maxlength="255" pattern="[A-Za-z0-9\s.,!?\x22\x27\x28\x29\x2d\x2f:]*" data-live-filter="description" placeholder="Freshly prepared, allergens, special instructions">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check2-circle me-1"></i>Create & Link
                        </button>
                    </div>
                </form>
            </div>

            {{-- TAB 3: Quick Add Stock Only --}}
            <div class="tab-pane fade" id="tab-quick-stock" role="tabpanel">
                <form method="POST" action="{{ route('admin.cafeteria.stock.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <div class="alert alert-secondary py-2 mb-0 small">
                            <i class="bi bi-box-seam me-1"></i>Add fresh raw supplies or bulk stock items to inventory first without immediately creating a public menu item.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="quick-stock-name" class="form-label fw-semibold">Stock Item Name <span class="text-danger">*</span></label>
                        <input id="quick-stock-name" name="name" class="form-control" maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x2d]*" data-live-filter="letter-start-name" placeholder="e.g. Fresh Milk Carton / Cooking Oil" required>
                    </div>
                    <div class="col-md-2">
                        <label for="quick-stock-unit" class="form-label fw-semibold">Unit <span class="text-danger">*</span></label>
                        <select id="quick-stock-unit" name="unit" class="form-select" required>
                            <option value="portion">portion</option>
                            <option value="piece">piece</option>
                            <option value="cup">cup</option>
                            <option value="plate">plate</option>
                            <option value="pack" selected>pack</option>
                            <option value="bottle">bottle</option>
                            <option value="box">box</option>
                            <option value="kg">kg</option>
                            <option value="liter">liter</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="quick-stock-qty" class="form-label fw-semibold">Initial Quantity <span class="text-danger">*</span></label>
                        <input id="quick-stock-qty" name="initial_quantity" type="number" min="0" max="100000" class="form-control" value="100" required>
                    </div>
                    <div class="col-md-2">
                        <label for="quick-stock-branch" class="form-label fw-semibold">Campus Branch</label>
                        <select id="quick-stock-branch" name="branch_id" class="form-select">
                            <option value="">All Campuses (Campus-Wide)</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(session('active_branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-box-arrow-in-down me-1"></i>Add Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Active Cafeteria Menu Items</h5>
        <span class="badge bg-secondary">{{ $items->count() }} items listed</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 180px;">Item & Description</th>
                    <th>Campus Availability</th>
                    <th style="min-width: 220px;">Stock On Hand</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th style="min-width: 260px;">Manage & Restock</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $item->name }}</div>
                        <div class="small text-muted">{{ $item->description ?? 'No description provided' }}</div>
                    </td>
                    <td>
                        @if ($item->branch)
                            <span class="badge bg-primary-subtle text-primary border"><i class="bi bi-geo-alt me-1"></i>{{ $item->branch->name }}</span>
                        @else
                            <span class="badge bg-success-subtle text-success border"><i class="bi bi-globe me-1"></i>Campus-Wide</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold fs-6 {{ $item->inventoryItem->quantity_on_hand <= $item->inventoryItem->reorder_level ? 'text-danger' : 'text-dark' }}">
                                {{ $item->inventoryItem->quantity_on_hand }} {{ $item->inventoryItem->unit }}
                            </span>
                            @if ($item->inventoryItem->branch)
                                <span class="badge bg-light text-secondary border small">{{ $item->inventoryItem->branch->name }}</span>
                            @else
                                <span class="badge bg-light text-secondary border small">Central</span>
                            @endif
                        </div>
                        {{-- Quick Restock Form --}}
                        <form method="POST" action="{{ route('admin.cafeteria.stock.restock', $item->inventory_item_id) }}" class="d-flex align-items-center gap-1 mt-1">
                            @csrf
                            <input type="number" name="quantity" min="1" max="10000" class="form-control form-control-sm" style="width: 75px;" placeholder="+qty" required>
                            <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" title="Quickly add fresh stock">
                                <i class="bi bi-plus-lg"></i> Restock
                            </button>
                        </form>
                    </td>
                    <td>
                        <span class="fw-semibold text-success">{{ number_format((float) $item->price, 2) }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $item->is_available ? 'bg-success' : 'bg-secondary' }}">
                            {{ $item->is_available ? 'Available' : 'Unavailable' }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.cafeteria.items.update', $item) }}" class="d-flex flex-wrap gap-1 align-items-center">
                            @csrf @method('PUT')
                            <select name="inventory_item_id" class="form-select form-select-sm" style="width: 140px;" aria-label="Stock item" required>
                                @foreach ($inventoryItems as $inventoryItem)
                                    <option value="{{ $inventoryItem->id }}" @selected($item->inventory_item_id === $inventoryItem->id)>
                                        {{ $inventoryItem->name }} ({{ $inventoryItem->branch ? $inventoryItem->branch->name : 'Central' }})
                                    </option>
                                @endforeach
                            </select>
                            <input name="name" value="{{ $item->name }}" class="form-control form-control-sm" style="width: 110px;" aria-label="Menu name" maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x2d]*" data-live-filter="letter-start-name" required>
                            <input name="price" value="{{ $item->price }}" type="number" min="0.01" step="0.01" class="form-control form-control-sm" style="width: 75px;" aria-label="Price" required>
                            <select name="branch_id" class="form-select form-select-sm" style="width: 110px;" aria-label="Campus branch">
                                <option value="" @selected($item->branch_id === null)>Campus-Wide</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($item->branch_id == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <select name="is_available" class="form-select form-select-sm" style="width: 95px;" aria-label="Availability">
                                <option value="1" @selected($item->is_available)>Available</option>
                                <option value="0" @selected(! $item->is_available)>Unavailable</option>
                            </select>
                            <button class="btn btn-sm btn-outline-primary py-1 px-2" title="Save changes">Save</button>
                        </form>
                        <form method="POST" action="{{ route('admin.cafeteria.items.destroy', $item) }}" class="mt-1 d-inline" onsubmit="return confirm('Delete this menu item only when it has no order history?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-link text-danger p-0 text-decoration-none small"><i class="bi bi-trash me-1"></i>Delete unused item</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No menu items have been added. Choose a stock above or create fresh stock to get started.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Student Cafeteria Orders</h5>
        <small class="text-muted">Live fulfillment tracking</small>
    </div>
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
