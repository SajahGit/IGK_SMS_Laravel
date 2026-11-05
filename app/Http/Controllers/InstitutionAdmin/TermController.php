<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\TermRequest;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TermController extends Controller
{
    public function index(Request $request): View
    {
        $selectedYearId = $request->query('academic_year_id');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        $termsQuery = Term::with('academicYear')->withCount('exams');

        if ($selectedYearId) {
            $termsQuery->where('academic_year_id', $selectedYearId);
        }

        $terms = $termsQuery->orderByDesc('start_date')->orderBy('sequence')->get();

        return view('InstitutionAdmin.InstitutionAdminTermsIndex', compact('terms', 'academicYears', 'selectedYearId'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.terms.index');
    }

    public function edit(Term $term): RedirectResponse
    {
        return redirect()->route('admin.terms.index', ['academic_year_id' => $term->academic_year_id]);
    }

    public function store(TermRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $isCurrent = $request->boolean('is_current');
        $data['is_current'] = $isCurrent;

        DB::transaction(function () use ($data, $isCurrent) {
            if ($isCurrent) {
                Term::where('is_current', true)->get()
                    ->each(fn (Term $currentTerm) => $currentTerm->update(['is_current' => false]));
            }

            Term::create($data);
        });

        return redirect()->route('admin.terms.index', ['academic_year_id' => $data['academic_year_id']])
            ->with('success', 'Term created successfully.');
    }

    public function update(TermRequest $request, Term $term): RedirectResponse
    {
        $data = $request->validated();
        $isCurrent = $request->boolean('is_current');
        $data['is_current'] = $isCurrent;

        DB::transaction(function () use ($data, $isCurrent, $term) {
            if ($isCurrent) {
                Term::where('id', '!=', $term->id)
                    ->where('is_current', true)
                    ->get()
                    ->each(fn (Term $currentTerm) => $currentTerm->update(['is_current' => false]));
            }

            $term->update($data);
        });

        return redirect()->route('admin.terms.index', ['academic_year_id' => $data['academic_year_id']])
            ->with('success', 'Term updated successfully.');
    }

    public function setCurrent(Term $term): RedirectResponse
    {
        DB::transaction(function () use ($term) {
            Term::where('is_current', true)->get()
                ->each(fn (Term $currentTerm) => $currentTerm->update(['is_current' => false]));
            $term->update(['is_current' => true]);
        });

        return redirect()->route('admin.terms.index', ['academic_year_id' => $term->academic_year_id])
            ->with('success', "Term '{$term->name}' is now set as current.");
    }

    public function destroy(Term $term): RedirectResponse
    {
        if ($term->exams()->exists()) {
            return back()->with('error', 'Cannot delete a term that has scheduled exams.');
        }

        $yearId = $term->academic_year_id;
        $term->delete();

        return redirect()->route('admin.terms.index', ['academic_year_id' => $yearId])
            ->with('success', 'Term deleted successfully.');
    }
}
