<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\User;
use App\Rules\ValidInternationalPhone;
use App\Rules\ValidPersonName;
use App\Support\InternationalPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class GuardianController extends Controller
{
    public function index(Request $request)
    {
        $guardians = Guardian::with(['user', 'students.user'])
            ->when($request->q, fn ($query, $s) => $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('Admin.AdminGuardiansIndex', compact('guardians'));
    }

    public function create()
    {
        return view('Admin.AdminGuardiansCreate');
    }

    public function store(Request $request)
    {
        InternationalPhoneNumber::normalizeRequest($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new ValidPersonName],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|max:32',
            'phone' => ['required', 'string', 'max:20', Rule::unique('guardians', 'phone'), new ValidInternationalPhone($request->input('phone_region'))],
            'nationality' => ['nullable', Rule::in(array_column(config('school.demo_nationalities', []), 'label'))],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'parent',
            ]);
            if (Role::where('name', 'Guardian')->exists()) {
                $user->syncRoles(['Guardian']);
            }
            Guardian::create([
                'user_id' => $user->id,
                'phone' => $data['phone'],
                'nationality' => $data['nationality'] ?? null,
            ]);
        });

        return redirect()->route('admin.guardians.index')->with('success', 'Parent account created. Link children from the student edit page.');
    }

    public function edit(Guardian $guardian)
    {
        return view('Admin.AdminGuardiansEdit', ['guardian' => $guardian->load('user')]);
    }

    public function update(Request $request, Guardian $guardian)
    {
        InternationalPhoneNumber::normalizeRequest($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new ValidPersonName],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($guardian->user_id)],
            'password' => 'nullable|string|min:8|max:32',
            'phone' => ['required', 'string', 'max:20', Rule::unique('guardians', 'phone')->ignore($guardian), new ValidInternationalPhone($request->input('phone_region'))],
            'nationality' => ['nullable', Rule::in(array_column(config('school.demo_nationalities', []), 'label'))],
        ]);

        DB::transaction(function () use ($data, $guardian) {
            $user = $guardian->user;
            $user->name = $data['name'];
            $user->email = $data['email'];
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();
            $guardian->update([
                'phone' => $data['phone'],
                'nationality' => $data['nationality'] ?? null,
            ]);
        });

        return redirect()->route('admin.guardians.index')->with('success', 'Parent updated successfully.');
    }

    public function destroy(Guardian $guardian)
    {
        $guardian->user->delete(); // students keep existing; their guardian_id becomes empty

        return redirect()->route('admin.guardians.index')->with('success', 'Parent deleted.');
    }
}
