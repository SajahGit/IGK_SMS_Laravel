<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\CafeteriaItem;
use App\Models\CafeteriaOrder;
use App\Models\InventoryItem;
use App\Services\CafeteriaOrderService;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CafeteriaController extends Controller
{
    public function index(): View
    {
        $items = CafeteriaItem::query()->with('inventoryItem')->orderBy('name')->get();
        $orders = CafeteriaOrder::query()
            ->with(['student.user', 'items.cafeteriaItem'])
            ->latest('order_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
        $inventoryItems = InventoryItem::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('InstitutionAdmin.CafeteriaIndex', compact('items', 'orders', 'inventoryItems'));
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $this->validateItem($request);
        $data['is_available'] = true;
        $item = CafeteriaItem::create($data + ['is_available' => true]);
        AuditLogger::log('cafeteria_item_created', $item, null, $item->toArray());

        return redirect()->route('admin.cafeteria.index')->with('success', 'Cafeteria menu item created.');
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
        $old = $item->toArray();
        $item->update($data);
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
            'is_available' => [$item ? 'required' : 'sometimes', 'boolean'],
        ]);
        $inventoryItem = InventoryItem::query()->findOrFail($data['inventory_item_id']);
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
