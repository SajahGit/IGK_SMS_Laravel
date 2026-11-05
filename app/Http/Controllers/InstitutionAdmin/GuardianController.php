<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class GuardianController extends Controller
{
    public function index(Request $request)
    {
        $guardians = Guardian::with(['user', 'students.user'])
            ->when($request->q, fn ($query, $s) => $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('InstitutionAdmin.InstitutionAdminGuardiansIndex', compact('guardians'));
    }

    public function create()
    {
        return view('InstitutionAdmin.InstitutionAdminGuardiansCreate');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', Password::defaults()],
            'phone' => 'required|max:20',
        ]);

        $guardian = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'parent',
                'must_change_password' => true,
            ]);

            return Guardian::create(['user_id' => $user->id, 'phone' => $data['phone']]);
        });
        AuditLogger::log('created_guardian', $guardian, null, $guardian->toArray());

        return redirect()->route('admin.guardians.index')->with('success', 'Parent account created. Link children from the student edit page.');
    }

    public function edit(Guardian $guardian)
    {
        return view('InstitutionAdmin.InstitutionAdminGuardiansEdit', ['guardian' => $guardian->load('user')]);
    }

    public function update(Request $request, Guardian $guardian)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($guardian->user_id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'phone' => 'required|max:20',
        ]);

        $before = $guardian->toArray();
        DB::transaction(function () use ($data, $guardian) {
            $user = $guardian->user;
            $user->name = $data['name'];
            $user->email = $data['email'];
            if (! empty($data['password'])) {
                $user->password = $data['password'];
                $user->must_change_password = true;
            }
            $user->save();
            $guardian->update(['phone' => $data['phone']]);
        });
        AuditLogger::log('updated_guardian', $guardian, $before, $guardian->toArray());

        return redirect()->route('admin.guardians.index')->with('success', 'Parent updated successfully.');
    }

    public function destroy(Guardian $guardian)
    {
        $before = $guardian->toArray();
        $guardian->user->delete(); // Students remain linked to any other guardian.
        AuditLogger::log('deleted_guardian', $guardian, $before, null);

        return redirect()->route('admin.guardians.index')->with('success', 'Parent deleted.');
    }
}
