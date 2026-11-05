<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\StoreCurriculumRequest;
use App\Http\Requests\InstitutionAdmin\UpdateCurriculumRequest;
use App\Models\Curriculum;
use App\Models\Program;
use App\Models\Subject;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CurriculumController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Curriculum::class);

        $programId = request()->query('program_id');

        $curricula = Curriculum::with(['program.department', 'subject'])
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->orderBy('program_id')
            ->orderBy('term_number')
            ->orderBy('subject_id')
            ->paginate(15)
            ->withQueryString();

        $programs = Program::where('is_active', true)->with('department')->orderBy('name')->get();

        return view('InstitutionAdmin.InstitutionAdminCurriculaIndex', compact('curricula', 'programs', 'programId'));
    }

    public function create(): View
    {
        $this->authorize('create', Curriculum::class);

        $programs = Program::where('is_active', true)->with('department')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();

        return view('InstitutionAdmin.InstitutionAdminCurriculaCreate', compact('programs', 'subjects'));
    }

    public function store(StoreCurriculumRequest $request): RedirectResponse
    {
        $this->authorize('create', Curriculum::class);

        $curriculum = Curriculum::create($request->validated());
        AuditLogger::log('created_curriculum', $curriculum, null, $curriculum->toArray());

        return redirect()->route('admin.curricula.index')
            ->with('success', 'Curriculum item assigned successfully.');
    }

    public function edit(Curriculum $curriculum): View
    {
        $this->authorize('update', $curriculum);

        $curriculum->load(['program', 'subject']);
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();

        return view('InstitutionAdmin.InstitutionAdminCurriculaEdit', compact('curriculum', 'programs', 'subjects'));
    }

    public function update(UpdateCurriculumRequest $request, Curriculum $curriculum): RedirectResponse
    {
        $this->authorize('update', $curriculum);

        $before = $curriculum->toArray();
        $curriculum->update($request->validated());
        AuditLogger::log('updated_curriculum', $curriculum, $before, $curriculum->toArray());

        return redirect()->route('admin.curricula.index')
            ->with('success', 'Curriculum item updated successfully.');
    }

    public function destroy(Curriculum $curriculum): RedirectResponse
    {
        $this->authorize('delete', $curriculum);

        $before = $curriculum->toArray();
        $curriculum->delete();
        AuditLogger::log('deleted_curriculum', $curriculum, $before, null);

        return redirect()->route('admin.curricula.index')
            ->with('success', 'Curriculum item removed.');
    }
}
