<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\SavedFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedFilterController extends Controller
{
    public function storeStudents(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'filters' => ['required', 'array'],
            'filters.q' => ['nullable', 'string', 'max:100'],
            'filters.class_id' => ['nullable', 'integer', 'exists:classes,id'],
        ]);

        $this->store($request, 'students', $data);

        return back()->with('success', 'Student filter saved.');
    }

    public function storeInvoices(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'filters' => ['required', 'array'],
            'filters.q' => ['nullable', 'string', 'max:100'],
            'filters.class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'filters.status' => ['nullable', 'in:draft,issued,partially_paid,paid,cancelled'],
        ]);

        $this->store($request, 'invoices', $data);

        return back()->with('success', 'Invoice filter saved.');
    }

    public function destroyStudents(Request $request, int $savedFilter): RedirectResponse
    {
        $this->destroy($request, $savedFilter, 'students');

        return back()->with('success', 'Student filter deleted.');
    }

    public function destroyInvoices(Request $request, int $savedFilter): RedirectResponse
    {
        $this->destroy($request, $savedFilter, 'invoices');

        return back()->with('success', 'Invoice filter deleted.');
    }

    private function store(Request $request, string $scope, array $data): void
    {
        $allowedFilters = $scope === 'students'
            ? ['q', 'class_id']
            : ['q', 'class_id', 'status'];
        $filters = array_filter(
            array_intersect_key($data['filters'], array_flip($allowedFilters)),
            fn (mixed $value) => $value !== null && $value !== ''
        );

        SavedFilter::create([
            'user_id' => $request->user()->id,
            'scope' => $scope,
            'name' => $data['name'],
            'filters' => $filters,
        ]);
    }

    private function destroy(Request $request, int $savedFilterId, string $scope): void
    {
        SavedFilter::query()
            ->whereKey($savedFilterId)
            ->where('user_id', $request->user()->id)
            ->where('scope', $scope)
            ->firstOrFail()
            ->delete();
    }
}
