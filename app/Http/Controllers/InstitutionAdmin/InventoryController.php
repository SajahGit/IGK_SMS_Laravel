<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Inventory\StoreInventoryMovementRequest;
use App\Http\Requests\Inventory\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeInventory('inventory.read');

        $items = InventoryItem::query()
            ->when($request->filled('search'), fn ($query) => $query->where(function ($nested) use ($request) {
                $nested->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('sku', 'like', '%'.$request->string('search').'%')
                    ->orWhere('category', 'like', '%'.$request->string('search').'%');
            }))
            ->when($request->boolean('low_stock'), fn ($query) => $query->whereColumn('quantity_on_hand', '<=', 'reorder_level'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('InstitutionAdmin.InventoryIndex', compact('items'));
    }

    public function create(): View
    {
        $this->authorizeInventory('inventory.create');

        return view('InstitutionAdmin.InventoryForm', ['inventoryItem' => null]);
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $initialQuantity = $data['initial_quantity'];
        unset($data['initial_quantity'], $data['reference']);
        $data['quantity_on_hand'] = 0;
        $data['is_active'] = $request->boolean('is_active');

        $item = DB::transaction(function () use ($data, $initialQuantity, $request) {
            $item = InventoryItem::create($data);
            if ($initialQuantity > 0) {
                InventoryService::recordMovement(
                    $item,
                    'receipt',
                    $initialQuantity,
                    $request->validated('reference'),
                    'Opening stock',
                    $request->user()->id
                );
            }

            AuditLogger::log('created_inventory_item', $item, null, $item->toArray());

            return $item;
        });

        return redirect()->route('admin.inventory.show', $item)
            ->with('success', 'Inventory item created.');
    }

    public function show(InventoryItem $inventoryItem): View
    {
        $this->authorizeInventory('inventory.read');
        $inventoryItem->load(['movements.recorder']);
        $movements = $inventoryItem->movements()->latest()->paginate(20);

        return view('InstitutionAdmin.InventoryShow', compact('inventoryItem', 'movements'));
    }

    public function edit(InventoryItem $inventoryItem): View
    {
        $this->authorizeInventory('inventory.update');

        return view('InstitutionAdmin.InventoryForm', compact('inventoryItem'));
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $before = $inventoryItem->toArray();
        $inventoryItem->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        AuditLogger::log('updated_inventory_item', $inventoryItem, $before, $inventoryItem->toArray());

        return redirect()->route('admin.inventory.show', $inventoryItem)
            ->with('success', 'Inventory item updated.');
    }

    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorizeInventory('inventory.delete');

        if ($inventoryItem->movements()->exists()) {
            throw ValidationException::withMessages([
                'inventory' => 'Items with stock history cannot be deleted. Deactivate the item instead.',
            ]);
        }

        $before = $inventoryItem->toArray();
        $inventoryItem->delete();
        AuditLogger::log('deleted_inventory_item', $inventoryItem, $before, null);

        return redirect()->route('admin.inventory.index')->with('success', 'Inventory item deleted.');
    }

    public function movement(StoreInventoryMovementRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        InventoryService::recordMovement(
            $inventoryItem,
            $request->validated('type'),
            (int) $request->validated('quantity'),
            $request->validated('reference'),
            $request->validated('notes'),
            $request->user()->id
        );

        return redirect()->route('admin.inventory.show', $inventoryItem)->with('success', 'Stock movement recorded.');
    }

    private function authorizeInventory(string $permission): void
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->can($permission), 403);
    }
}
