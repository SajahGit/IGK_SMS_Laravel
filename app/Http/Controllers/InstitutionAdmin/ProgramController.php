<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\StoreProgramRequest;
use App\Http\Requests\InstitutionAdmin\UpdateProgramRequest;
use App\Models\Department;
use App\Models\Program;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Program::class);

        $programs = Program::with('department')->orderBy('name')->paginate(10)->withQueryString();

        return view('InstitutionAdmin.InstitutionAdminProgramsIndex', compact('programs'));
    }

    public function create(): View
    {
        $this->authorize('create', Program::class);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('InstitutionAdmin.InstitutionAdminProgramsCreate', compact('departments'));
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $this->authorize('create', Program::class);

        $program = Program::create($request->validated());
        AuditLogger::log('created_program', $program, null, $program->toArray());

        return redirect()->route('admin.programs.index')
            ->with('success', 'Program created successfully.');
    }

    public function edit(Program $program): View
    {
        $this->authorize('update', $program);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('InstitutionAdmin.InstitutionAdminProgramsEdit', compact('program', 'departments'));
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $this->authorize('update', $program);

        $before = $program->toArray();
        $program->update($request->validated());
        AuditLogger::log('updated_program', $program, $before, $program->toArray());

        return redirect()->route('admin.programs.index')
            ->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $this->authorize('delete', $program);

        $before = $program->toArray();
        $program->delete();
        AuditLogger::log('deleted_program', $program, $before, null);

        return redirect()->route('admin.programs.index')
            ->with('success', 'Program deleted.');
    }
}
