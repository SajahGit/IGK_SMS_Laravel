<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\AcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $academicYears = AcademicYear::withCount('terms')
            ->orderByDesc('start_date')
            ->get();

        return view('InstitutionAdmin.InstitutionAdminAcademicYearsIndex', compact('academicYears'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.academic-years.index');
    }

    public function edit(AcademicYear $academicYear): RedirectResponse
    {
        return redirect()->route('admin.academic-years.index');
    }

    public function store(AcademicYearRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $isCurrent = $request->boolean('is_current');
        $data['is_current'] = $isCurrent;

        DB::transaction(function () use ($data, $isCurrent) {
            if ($isCurrent) {
                AcademicYear::where('is_current', true)->get()
                    ->each(fn (AcademicYear $year) => $year->update(['is_current' => false]));
            }

            AcademicYear::create($data);
        });

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Academic year created successfully.');
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $data = $request->validated();
        $isCurrent = $request->boolean('is_current');
        $data['is_current'] = $isCurrent;

        DB::transaction(function () use ($data, $isCurrent, $academicYear) {
            if ($isCurrent) {
                AcademicYear::where('id', '!=', $academicYear->id)
                    ->where('is_current', true)
                    ->get()
                    ->each(fn (AcademicYear $year) => $year->update(['is_current' => false]));
            }

            $academicYear->update($data);
        });

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Academic year updated successfully.');
    }

    public function setCurrent(AcademicYear $academicYear): RedirectResponse
    {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::where('is_current', true)->get()
                ->each(fn (AcademicYear $year) => $year->update(['is_current' => false]));
            $academicYear->update(['is_current' => true]);
        });

        return redirect()->route('admin.academic-years.index')
            ->with('success', "Academic year '{$academicYear->name}' is now set as current.");
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->terms()->exists()) {
            return back()->with('error', 'Cannot delete an academic year that has associated terms.');
        }

        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Academic year deleted successfully.');
    }
}
