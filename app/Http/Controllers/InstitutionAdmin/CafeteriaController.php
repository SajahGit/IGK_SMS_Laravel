<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CafeteriaItem;
use App\Models\CafeteriaOrder;
use App\Models\InventoryItem;
use App\Models\Scopes\BranchOwned;
use App\Services\CafeteriaOrderService;
use App\Services\InventoryService;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CafeteriaController extends Controller
{
    public function index(): View
    {
        $items = CafeteriaItem::withoutGlobalScope(BranchOwned::class)
            ->with(['inventoryItem.branch', 'branch'])
            ->orderByDesc('updated_at')
            ->latest('id')
            ->get();

        $orders = CafeteriaOrder::query()
            ->with(['student.user', 'items.cafeteriaItem'])
            ->orderByDesc('updated_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $inventoryItems = InventoryItem::withoutGlobalScope(BranchOwned::class)
            ->with('branch')
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->latest('id')
            ->get();

        $linkedInventoryItemIds = CafeteriaItem::withoutGlobalScope(BranchOwned::class)
            ->pluck('inventory_item_id')
            ->all();

        $branches = Branch::withoutGlobalScope('assigned-branch')->where('is_active', true)->orderBy('name')->get();

        return view('InstitutionAdmin.CafeteriaIndex', compact('items', 'orders', 'inventoryItems', 'linkedInventoryItemIds', 'branches'));
    }

    public function storeItem(Request $request): RedirectResponse
    {
        if ($request->input('stock_source') === 'fresh' || $request->filled('fresh_stock_quantity')) {
            return $this->storeFreshStockMenuItem($request);
        }

        $data = $this->validateItem($request);
        $data['is_available'] = true;

        $inventoryItem = InventoryItem::withoutGlobalScope(BranchOwned::class)->findOrFail($data['inventory_item_id']);

        if ($request->has('branch_id')) {
            $data['branch_id'] = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        } else {
            $data['branch_id'] = \App\Support\BranchContext::activeBranchId($request->user()) ?? $inventoryItem->branch_id;
        }

        $item = CafeteriaItem::create($data);
        AuditLogger::log('cafeteria_item_created', $item, null, $item->toArray());

        return redirect()->route('admin.cafeteria.index')->with('success', 'Cafeteria menu item created.');
    }

    private function storeFreshStockMenuItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z][A-Za-z0-9\s()\-]*\z/'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', function ($attribute, $value, $fail): void {
                if (! Money::hasValidScale($value)) {
                    $fail('The price must be at least 0.01 with no more than two decimal places.');
                }
            }],
            'description' => ['nullable', 'string', 'max:255', 'regex:/\A[A-Za-z0-9\s.,!?\'"()\-:\/]*\z/'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'unit' => ['required', 'string', 'max:40', 'regex:/\A[A-Za-z\s-]+\z/'],
            'fresh_stock_quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'reorder_level' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'category' => ['nullable', 'string', 'max:40', 'regex:/\A[A-Za-z\s-]+\z/'],
        ]);

        $item = DB::transaction(function () use ($validated, $request) {
            $branchId = $request->filled('branch_id') ? (int) $validated['branch_id'] : null;
            $category = $validated['category'] ?? 'Cafeteria';
            $unit = $validated['unit'];
            $quantity = (int) $validated['fresh_stock_quantity'];
            $reorderLevel = (int) ($validated['reorder_level'] ?? 5);

            $cleanName = preg_replace('/[^A-Za-z0-9]/', '', substr($validated['name'], 0, 8));
            $baseSku = 'CAF-' . strtoupper(! empty($cleanName) ? $cleanName : 'ITEM');
            $sku = $baseSku . '-' . rand(1000, 9999);
            while (InventoryItem::withoutGlobalScopes()->where('sku', $sku)->exists()) {
                $sku = $baseSku . '-' . rand(1000, 9999);
            }

            $inventoryItem = InventoryItem::create([
                'sku' => $sku,
                'branch_id' => $branchId,
                'name' => $validated['name'],
                'category' => $category,
                'unit' => $unit,
                'quantity_on_hand' => 0,
                'reorder_level' => $reorderLevel,
                'description' => $validated['description'] ?? 'Cafeteria fresh stock item',
                'is_active' => true,
            ]);

            if ($quantity > 0) {
                InventoryService::recordMovement(
                    $inventoryItem,
                    'receipt',
                    $quantity,
                    null,
                    'Initial fresh stock for cafeteria menu item',
                    $request->user()->id
                );
            }

            $cafeteriaItem = CafeteriaItem::create([
                'inventory_item_id' => $inventoryItem->id,
                'branch_id' => $branchId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'is_available' => true,
            ]);

            AuditLogger::log('created_inventory_item', $inventoryItem, null, $inventoryItem->toArray());
            AuditLogger::log('cafeteria_item_created', $cafeteriaItem, null, $cafeteriaItem->toArray());

            return $cafeteriaItem;
        });

        return redirect()->route('admin.cafeteria.index')->with('success', "Fresh stock '{$item->name}' created and added to cafeteria menu.");
    }

    public function quickStoreStock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z][A-Za-z0-9\s()\-]*\z/'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'unit' => ['required', 'string', 'max:40', 'regex:/\A[A-Za-z\s-]+\z/'],
            'initial_quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'reorder_level' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'category' => ['nullable', 'string', 'max:40', 'regex:/\A[A-Za-z\s-]+\z/'],
        ]);

        $item = DB::transaction(function () use ($validated, $request) {
            $branchId = $request->filled('branch_id') ? (int) $validated['branch_id'] : null;
            $category = $validated['category'] ?? 'Cafeteria';
            $unit = $validated['unit'];
            $quantity = (int) $validated['initial_quantity'];
            $reorderLevel = (int) ($validated['reorder_level'] ?? 5);

            $cleanName = preg_replace('/[^A-Za-z0-9]/', '', substr($validated['name'], 0, 8));
            $baseSku = 'STK-' . strtoupper(! empty($cleanName) ? $cleanName : 'ITEM');
            $sku = $baseSku . '-' . rand(1000, 9999);
            while (InventoryItem::withoutGlobalScopes()->where('sku', $sku)->exists()) {
                $sku = $baseSku . '-' . rand(1000, 9999);
            }

            $inventoryItem = InventoryItem::create([
                'sku' => $sku,
                'branch_id' => $branchId,
                'name' => $validated['name'],
                'category' => $category,
                'unit' => $unit,
                'quantity_on_hand' => 0,
                'reorder_level' => $reorderLevel,
                'description' => 'Fresh stock item added from Cafeteria',
                'is_active' => true,
            ]);

            if ($quantity > 0) {
                InventoryService::recordMovement(
                    $inventoryItem,
                    'receipt',
                    $quantity,
                    null,
                    'Fresh stock receipt from Cafeteria',
                    $request->user()->id
                );
            }

            AuditLogger::log('created_inventory_item', $inventoryItem, null, $inventoryItem->toArray());

            return $inventoryItem;
        });

        return redirect()->route('admin.cafeteria.index')->with('success', "Fresh inventory stock '{$item->name}' ({$item->quantity_on_hand} {$item->unit}) created.");
    }

    public function restock(Request $request, int $inventoryItemId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $inventoryItem = InventoryItem::withoutGlobalScope(BranchOwned::class)->findOrFail($inventoryItemId);

        InventoryService::recordMovement(
            $inventoryItem,
            'receipt',
            (int) $validated['quantity'],
            null,
            $validated['notes'] ?? 'Fresh restock from Cafeteria',
            $request->user()->id
        );

        return redirect()->route('admin.cafeteria.index')->with('success', "Added {$validated['quantity']} {$inventoryItem->unit} fresh stock to {$inventoryItem->name}.");
    }

    public function updateItem(Request $request, CafeteriaItem $item): RedirectResponse
    {
        $data = $this->validateItem($request, $item);
        if ($item->orderItems()->exists() && (int) $data['inventory_item_id'] !== $item->inventory_item_id) {
            throw ValidationException::withMessages([
                'inventory_item_id' => 'The linked stock item cannot change after this menu item has order history.',
            ]);
        }
        $data['is_available'] = $request->boolean('is_available');

        if ($request->has('branch_id')) {
            $data['branch_id'] = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        } elseif ($item->branch_id === null) {
            $inventoryItem = InventoryItem::withoutGlobalScopes()->find($data['inventory_item_id']);
            $data['branch_id'] = \App\Support\BranchContext::activeBranchId($request->user()) ?? $inventoryItem?->branch_id;
        }

        $old = $item->toArray();
        $item->update($data);
        $item->touch();
        AuditLogger::log('cafeteria_item_updated', $item, $old, $item->toArray());

        return redirect()->route('admin.cafeteria.index')->with('success', 'Cafeteria menu item updated.');
    }

    public function destroyItem(CafeteriaItem $item): RedirectResponse
    {
        if ($item->orderItems()->exists()) {
            return back()->withErrors(['item' => 'A menu item with order history cannot be deleted. Mark it unavailable instead.']);
        }

        $before = $item->toArray();
        $item->delete();
        AuditLogger::log('cafeteria_item_deleted', $item, $before, null);

        return redirect()->route('admin.cafeteria.index')->with('success', 'Cafeteria menu item deleted.');
    }

    public function fulfill(CafeteriaOrder $order, CafeteriaOrderService $service): RedirectResponse
    {
        $service->fulfill($order);

        return redirect()->route('admin.cafeteria.index')->with('success', 'Cafeteria order fulfilled.');
    }

    public function cancel(CafeteriaOrder $order, CafeteriaOrderService $service): RedirectResponse
    {
        $service->cancel($order);

        return redirect()->route('admin.cafeteria.index')->with('success', 'Cafeteria order cancelled and reserved stock returned.');
    }

    private function validateItem(Request $request, ?CafeteriaItem $item = null): array
    {
        $data = $request->validate([
            'inventory_item_id' => [
                'required',
                'integer',
                Rule::exists('inventory_items', 'id'),
                Rule::unique('cafeteria_items', 'inventory_item_id')->ignore($item?->id),
            ],
            'name' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z][A-Za-z0-9\s()\-]*\z/'],
            'description' => ['nullable', 'string', 'max:255', 'regex:/\A[A-Za-z0-9\s.,!?\'"()\-:\/]*\z/'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', function ($attribute, $value, $fail): void {
                if (! Money::hasValidScale($value)) {
                    $fail('The price must be at least 0.01 with no more than two decimal places.');
                }
            }],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_available' => [$item ? 'required' : 'sometimes', 'boolean'],
        ]);

        $inventoryItem = InventoryItem::withoutGlobalScope(BranchOwned::class)->findOrFail($data['inventory_item_id']);
        if (! $inventoryItem->is_active && ($item === null || $request->boolean('is_available'))) {
            throw ValidationException::withMessages([
                'inventory_item_id' => 'An unavailable menu item cannot use inactive inventory stock.',
            ]);
        }
        if ($item) {
            unset($data['is_available']);
        }

        return $data;
    }
}
