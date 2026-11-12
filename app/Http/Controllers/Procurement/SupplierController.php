<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreSupplierRequest;
use App\Http\Requests\Procurement\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $this->authorize('suppliers.read');
        $suppliers = Supplier::withCount('purchaseOrders')->orderBy('name')->paginate(15);

        return view('Procurement.SuppliersIndex', compact('suppliers'));
    }

    public function create(): View
    {
        $this->authorize('suppliers.create');

        return view('Procurement.SupplierForm', ['supplier' => null]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated() + ['is_active' => $request->boolean('is_active')]);
        AuditLogger::log('created_supplier', $supplier, null, $supplier->toArray());

        return redirect()->route('procurement.suppliers.index')->with('success', 'Supplier created.');
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('suppliers.update');

        return view('Procurement.SupplierForm', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $before = $supplier->toArray();
        $supplier->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        AuditLogger::log('updated_supplier', $supplier, $before, $supplier->toArray());

        return redirect()->route('procurement.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('suppliers.delete');
        if ($supplier->purchaseOrders()->exists()) {
            throw ValidationException::withMessages([
                'supplier' => 'Suppliers referenced by purchase orders cannot be deleted. Deactivate this supplier instead.',
            ]);
        }

        $before = $supplier->toArray();
        $supplier->delete();
        AuditLogger::log('deleted_supplier', $supplier, $before, null);

        return redirect()->route('procurement.suppliers.index')->with('success', 'Supplier deleted.');
    }
}
