<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\CancelPurchaseOrderRequest;
use App\Http\Requests\Procurement\ReceivePurchaseOrderRequest;
use App\Http\Requests\Procurement\StorePurchaseOrderRequest;
use App\Http\Requests\Procurement\UpdatePurchaseOrderRequest;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ProcurementService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('procurement.read');
        $orders = PurchaseOrder::with('supplier')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($query) => $query->where('order_no', 'like', '%'.$request->string('search').'%'))
            ->latest('order_date')
            ->paginate(15)
            ->withQueryString();

        return view('Procurement.PurchaseOrdersIndex', compact('orders'));
    }

    public function create(): View
    {
        $this->authorize('procurement.create');
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $inventoryItems = InventoryItem::where('is_active', true)->orderBy('name')->get();

        return view('Procurement.PurchaseOrderForm', [
            'order' => null,
            'suppliers' => $suppliers,
            'inventoryItems' => $inventoryItems,
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $order = ProcurementService::create($request->validated(), $request->user()->id);

        return redirect()->route('procurement.orders.show', $order)->with('success', 'Draft purchase order created.');
    }

    public function show(PurchaseOrder $order): View
    {
        $this->authorize('procurement.read');
        $order->load(['supplier', 'creator', 'approver', 'receiver', 'items.inventoryItem']);
        $total = $order->items->reduce(
            fn (string $total, $line) => Money::add($total, Money::mul($line->unit_cost, $line->quantity_ordered)),
            '0.00'
        );

        return view('Procurement.PurchaseOrderShow', compact('order', 'total'));
    }

    public function edit(PurchaseOrder $order): View
    {
        $this->authorize('procurement.update');
        $this->ensureDraft($order);
        $order->load('items');
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $inventoryItems = InventoryItem::where('is_active', true)->orderBy('name')->get();

        return view('Procurement.PurchaseOrderForm', compact('order', 'suppliers', 'inventoryItems'));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $order): RedirectResponse
    {
        ProcurementService::update($order, $request->validated());

        return redirect()->route('procurement.orders.show', $order)->with('success', 'Draft purchase order updated.');
    }

    public function destroy(PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('procurement.delete');
        ProcurementService::deleteDraft($order);

        return redirect()->route('procurement.orders.index')->with('success', 'Draft purchase order deleted.');
    }

    public function submit(PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('procurement.update');
        ProcurementService::submit($order);

        return back()->with('success', 'Purchase order submitted for approval.');
    }

    public function approve(PurchaseOrder $order, Request $request): RedirectResponse
    {
        $this->authorize('procurement.approve');
        ProcurementService::approve($order, $request->user()->id);

        return back()->with('success', 'Purchase order approved.');
    }

    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $order): RedirectResponse
    {
        ProcurementService::receive(
            $order,
            $request->validated('items'),
            $request->user()->id,
            $request->validated('reference'),
            $request->validated('notes')
        );

        return back()->with('success', 'Received quantities have been added to inventory.');
    }

    public function cancel(CancelPurchaseOrderRequest $request, PurchaseOrder $order): RedirectResponse
    {
        ProcurementService::cancel($order, $request->user()->id, $request->validated('reason'));

        return back()->with('success', 'Purchase order cancelled.');
    }

    private function ensureDraft(PurchaseOrder $order): void
    {
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages([
                'purchase_order' => 'Only draft purchase orders can be edited.',
            ]);
        }
    }
}
