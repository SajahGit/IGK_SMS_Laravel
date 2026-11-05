<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\GradeScale;
use App\Services\GradeScaleService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeScaleController extends Controller
{
    public function edit(): View
    {
        $scale = GradeScale::with('items')->where('is_default', true)->first();
        $items = $scale?->items->sortByDesc('min_percent')->values() ?? GradeScaleService::defaultItems();

        return view('InstitutionAdmin.InstitutionAdminGradeScaleEdit', compact('scale', 'items'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.min_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.max_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.grade' => ['required', 'string', 'max:10'],
            'items.*.grade_point' => ['required', 'numeric', 'min:0', 'max:4'],
            'items.*.is_pass' => ['required', 'boolean'],
        ]);
        $before = GradeScale::with('items')->where('is_default', true)->first();
        $scale = GradeScaleService::updateDefault($data['name'], $data['items']);
        AuditLogger::log(
            'updated_grade_scale',
            $scale,
            $before?->toArray(),
            $scale->toArray()
        );

        return redirect()->route('admin.grade-scale.edit')->with('success', 'Grade scale updated.');
    }
}
