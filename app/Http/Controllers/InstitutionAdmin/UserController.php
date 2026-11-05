<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\StoreUserRequest;
use App\Http\Requests\InstitutionAdmin\UpdateUserRequest;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::with(['roles'])
            ->when(! request()->user()->isSuperAdmin(), fn ($query) => $query
                ->where('role', '!=', 'super-admin')
                ->whereDoesntHave('roles', fn ($roles) => $roles->where('name', 'Super Admin')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        $roles = Role::whereNotIn('name', ['Student', 'student'])
            ->when(! request()->user()->isSuperAdmin(), fn ($query) => $query->where('name', '!=', 'Super Admin'))
            ->orderBy('name')
            ->get();

        return view('InstitutionAdmin.InstitutionAdminUsersIndex', compact('users', 'roles'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = Role::whereNotIn('name', ['Student', 'student'])
            ->when(! request()->user()->isSuperAdmin(), fn ($query) => $query->where('name', '!=', 'Super Admin'))
            ->orderBy('name')
            ->get();

        return view('InstitutionAdmin.InstitutionAdminUsersCreate', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $roleNames = $data['roles'];
        $primaryRole = $roleNames[0];

        $user = DB::transaction(function () use ($data, $roleNames, $primaryRole): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $this->roleValue($primaryRole),
                'is_active' => $data['is_active'] ?? true,
                'must_change_password' => true,
            ]);

            $user->syncRoles($roleNames);
            AuditLogger::log('roles_assigned', $user, null, ['roles' => $roleNames]);

            return $user;
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $roles = Role::when(! request()->user()->isSuperAdmin(), fn ($query) => $query->where('name', '!=', 'Super Admin'))
            ->orderBy('name')
            ->get();
        $roleNames = $user->getRoleNames()->all();
        if ($roleNames === []) {
            $roleNames = [match (strtolower($user->role)) {
                'admin', 'institution admin' => 'Institution Admin',
                'super-admin', 'super admin' => 'Super Admin',
                default => ucfirst($user->role),
            }];
        }

        return view('InstitutionAdmin.InstitutionAdminUsersEdit', compact('user', 'roles', 'roleNames'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        $roleNames = $data['roles'];
        $primaryRole = $roleNames[0];

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $this->roleValue($primaryRole),
            'is_active' => $data['is_active'] ?? false,
            'must_change_password' => $data['must_change_password'] ?? false,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
            $updateData['must_change_password'] = true;
        }

        $oldRoles = $user->getRoleNames()->all();
        sort($oldRoles);
        sort($roleNames);
        DB::transaction(function () use ($user, $updateData, $oldRoles, $roleNames): void {
            if ($user->id === auth()->id()
                && in_array('Super Admin', $oldRoles, true)
                && ! in_array('Super Admin', $roleNames, true)) {
                throw ValidationException::withMessages([
                    'roles' => 'You cannot remove your own Super Admin role.',
                ]);
            }

            if ($user->id === auth()->id()
                && $user->isSuperAdmin()
                && ! ($updateData['is_active'] ?? true)) {
                throw ValidationException::withMessages([
                    'is_active' => 'You cannot deactivate your own Super Admin account.',
                ]);
            }

            if (in_array('Super Admin', $oldRoles, true)
                && ! in_array('Super Admin', $roleNames, true)) {
                $this->preventRemovingLastSuperAdmin($user);
            }

            if (in_array('Super Admin', $oldRoles, true)
                && ! ($updateData['is_active'] ?? true)) {
                $this->preventRemovingLastSuperAdmin($user);
            }

            $user->update($updateData);
            $user->syncRoles($roleNames);

            if ($oldRoles !== $roleNames) {
                AuditLogger::log('roles_assigned', $user, ['roles' => $oldRoles], ['roles' => $roleNames]);
            }
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        DB::transaction(function () use ($user): void {
            if ($user->isSuperAdmin()) {
                $this->preventRemovingLastSuperAdmin($user);
            }

            $user->update(['is_active' => false]);
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'User deactivated.');
    }

    private function roleValue(string $roleName): string
    {
        return match ($roleName) {
            'Super Admin' => 'super-admin',
            'Institution Admin' => 'admin',
            default => strtolower($roleName),
        };
    }

    private function preventRemovingLastSuperAdmin(User $user): void
    {
        Role::query()->where('name', 'Super Admin')->lockForUpdate()->firstOrFail();
        $remaining = User::role('Super Admin')->where('users.id', '!=', $user->id)->count();

        if ($remaining === 0) {
            throw ValidationException::withMessages([
                'roles' => 'The last Super Admin role assignment cannot be removed.',
            ]);
        }
    }
}
