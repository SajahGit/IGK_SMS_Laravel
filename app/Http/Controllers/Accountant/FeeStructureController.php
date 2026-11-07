<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accountant\FeeStructureRequest;
use App\Models\AcademicYear;
use App\Models\FeeItem;
use App\Models\FeeStructure;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FeeStructureController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('fee_structures.read'), 403);

        $classId = $request->query('class_id');
        $programId = $request->query('program_id');
        $classes = SchoolClass::orderBy('name')->orderBy('section')->get();
        $terms = Term::orderBy('sequence')->get();
        $programs = Program::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        $feeStructures = FeeStructure::with(['schoolClass', 'term', 'program', 'academicYear', 'items'])
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->orderByRaw('class_id IS NULL')
            ->orderBy('class_id')
            ->get();

        return view('Accountant.AccountantFeeStructuresIndex', compact('feeStructures', 'classes', 'terms', 'classId', 'programs', 'academicYears', 'programId') + [
            'financeRoutePrefix' => $this->financeRoutePrefix(),
        ]);
    }

    public function store(FeeStructureRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $fee = FeeStructure::create($data);
        AuditLogger::log('created', $fee, null, $fee->toArray());

        return redirect()->route($this->financeRoutePrefix().'.fee-structures.index', $this->listFilter($fee))
            ->with('success', 'Fee structure created successfully.');
    }

    public function update(FeeStructureRequest $request, FeeStructure $feeStructure): RedirectResponse
    {
        $this->guardNotInvoiced($feeStructure, 'edited');

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        // With fee items the amount is derived (sum of mandatory items) and cannot be typed in.
        if ($feeStructure->items()->exists()) {
            unset($data['amount']);
        }

        $old = $feeStructure->toArray();
        $feeStructure->update($data);
        AuditLogger::log('updated', $feeStructure, $old, $feeStructure->toArray());

        return redirect()->route($this->financeRoutePrefix().'.fee-structures.index', $this->listFilter($feeStructure))
            ->with('success', 'Fee structure updated successfully.');
    }

    public function destroy(FeeStructure $feeStructure): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->can('fee_structures.delete'), 403);

        $this->guardNotInvoiced($feeStructure, 'deleted');

        $filter = $this->listFilter($feeStructure);
        AuditLogger::log('deleted', $feeStructure, $feeStructure->toArray(), null);
        $feeStructure->delete();

        return redirect()->route($this->financeRoutePrefix().'.fee-structures.index', $filter)
            ->with('success', 'Fee structure deleted.');
    }

    /**
     * SOP §11: "Cannot edit once invoices reference it - version by creating a new structure."
     * The rule itself lives in FeeStructure::isReferencedByInvoices().
     */
    private function guardNotInvoiced(FeeStructure $feeStructure, string $action): void
    {
        if ($feeStructure->isReferencedByInvoices()) {
            throw ValidationException::withMessages([
                'fee_structure' => "This fee structure cannot be {$action} because invoices already reference it. Create a new fee structure for future billing instead.",
            ]);
        }
    }

    public function storeItem(Request $request, FeeStructure $feeStructure): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('fee_structures.update'), 403);

        $this->guardNotInvoiced($feeStructure, 'changed');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('fee_items', 'name')->where('fee_structure_id', $feeStructure->id),
            ],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'is_mandatory' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($feeStructure, $data, $request) {
            $item = $feeStructure->items()->create([
                'name' => $data['name'],
                'amount' => $data['amount'],
                'is_mandatory' => $request->boolean('is_mandatory', true),
            ]);

            $feeStructure->syncAmountFromItems();
            AuditLogger::log('created', $item, null, $item->toArray());
        });

        return redirect()->route($this->financeRoutePrefix().'.fee-structures.index', $this->listFilter($feeStructure))
            ->with('success', 'Fee item added.');
    }

    public function destroyItem(Request $request, FeeItem $feeItem): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->can('fee_structures.update'), 403);

        $feeStructure = $feeItem->feeStructure;
        $this->guardNotInvoiced($feeStructure, 'changed');

        DB::transaction(function () use ($feeItem, $feeStructure) {
            AuditLogger::log('deleted', $feeItem, $feeItem->toArray(), null);
            $feeItem->delete();
            $feeStructure->syncAmountFromItems();
        });

        return redirect()->route($this->financeRoutePrefix().'.fee-structures.index', $this->listFilter($feeStructure))
            ->with('success', 'Fee item removed.');
    }

    private function financeRoutePrefix(): string
    {
        return request()->routeIs('accountant.*') ? 'accountant' : 'admin';
    }

    /** Keep the list filtered on the class (legacy) or program (SOP-exact) the structure belongs to. */
    private function listFilter(FeeStructure $feeStructure): array
    {
        return $feeStructure->class_id
            ? ['class_id' => $feeStructure->class_id]
            : ['program_id' => $feeStructure->program_id];
    }
}
