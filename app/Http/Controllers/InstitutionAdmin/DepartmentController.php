<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\StoreDepartmentRequest;
use App\Http\Requests\InstitutionAdmin\UpdateDepartmentRequest;
use App\Models\Department;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::withCount('programs')->orderBy('name')->paginate(10)->withQueryString();

        return view('InstitutionAdmin.InstitutionAdminDepartmentsIndex', compact('departments'));
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('InstitutionAdmin.InstitutionAdminDepartmentsCreate');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Department::class);

        $department = Department::create($request->validated());
        AuditLogger::log('created_department', $department, null, $department->toArray());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('InstitutionAdmin.InstitutionAdminDepartmentsEdit', compact('department'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $before = $department->toArray();
        $department->update($request->validated());
        AuditLogger::log('updated_department', $department, $before, $department->toArray());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $before = $department->toArray();
        $department->delete();
        AuditLogger::log('deleted_department', $department, $before, null);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted.');
    }
}
