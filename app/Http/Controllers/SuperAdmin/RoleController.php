<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreRoleRequest;
use App\Http\Requests\SuperAdmin\UpdateRoleRequest;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::with('permissions')->orderBy('name')->get();

        return view('SuperAdmin.SuperAdminRolesIndex', compact('roles'));
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        $permissions = Permission::orderBy('name')->get();

        return view('SuperAdmin.SuperAdminRolesCreate', compact('permissions'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name,
        ]);
        AuditLogger::log('created_role', $role, null, [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('name');

        return view('SuperAdmin.SuperAdminRolesEdit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $before = $role->only(['name', 'guard_name']);
        $role->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name,
        ]);
        AuditLogger::log('updated_role', $role, $before, $role->only(['name', 'guard_name']));

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if (in_array($role->name, [
            'Super Admin', 'Institution Admin', 'Registrar', 'Teacher',
            'Accountant', 'Student', 'Guardian', 'Librarian',
        ], true)) {
            throw ValidationException::withMessages([
                'role' => 'Built-in system roles cannot be deleted.',
            ]);
        }

        AuditLogger::log('deleted_role', $role, [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
        ], null);
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted.');
    }

    public function assignPermissions(Role $role, Request $request): RedirectResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('permissions', 'name')->where('guard_name', $role->guard_name),
            ],
        ]);
        $oldPermissions = $role->permissions->pluck('name')->all();
        $role->syncPermissions($validated['permissions'] ?? []);
        $newPermissions = $role->fresh()->permissions->pluck('name')->all();
        AuditLogger::log(
            'permissions_assigned',
            $role,
            ['permissions' => $oldPermissions],
            ['permissions' => $newPermissions]
        );

        return back()->with('success', 'Permissions updated.');
    }
}
