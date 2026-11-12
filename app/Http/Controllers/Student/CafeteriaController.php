<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CafeteriaItem;
use App\Models\CafeteriaOrder;
use App\Services\CafeteriaOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CafeteriaController extends Controller
{
    public function index(): View
    {
        $student = Auth::user()->student()->firstOrFail();
        $items = CafeteriaItem::query()
            ->where('is_available', true)
            ->whereHas('inventoryItem', fn ($query) => $query->where('is_active', true)->where('quantity_on_hand', '>', 0))
            ->with('inventoryItem:id,quantity_on_hand,unit')
            ->orderBy('name')
            ->get();
        $orders = CafeteriaOrder::query()
            ->where('student_id', $student->id)
            ->with('items.cafeteriaItem')
            ->latest('order_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('Student.Cafeteria', compact('items', 'orders'));
    }

    public function store(Request $request, CafeteriaOrderService $service): RedirectResponse
    {
        $data = $request->validate([
            'order_date' => ['required', 'date', 'after_or_equal:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.cafeteria_item_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('cafeteria_items', 'id')->where('is_available', true),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $data['items'] = array_values(array_filter(
            $data['items'],
            fn (array $line): bool => (int) $line['quantity'] > 0
        ));
        if ($data['items'] === []) {
            return back()->withErrors(['items' => 'Select a quantity for at least one menu item.'])->withInput();
        }
        $student = Auth::user()->student()->firstOrFail();
        $service->placeOrder($student, $data['order_date'], $data['items']);

        return redirect()->route('student.cafeteria')->with('success', 'Your order is reserved. The displayed total is not a payment receipt.');
    }

    public function cancel(CafeteriaOrder $order, CafeteriaOrderService $service): RedirectResponse
    {
        $student = Auth::user()->student()->firstOrFail();
        abort_unless($order->student_id === $student->id, 404);
        $service->cancel($order);

        return redirect()->route('student.cafeteria')->with('success', 'Order cancelled and reserved stock returned.');
    }
}
