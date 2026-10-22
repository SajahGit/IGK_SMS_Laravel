<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GuardianController extends Controller
{
    public function index(Request $request)
    {
        $guardians = Guardian::with(['user', 'students.user'])
            ->when($request->q, fn ($query, $s) => $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.AdminGuardiansIndex', compact('guardians'));
    }

    public function create()
    {
        return view('admin.AdminGuardiansCreate');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'phone' => 'required|max:20',
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'parent',
            ]);
            Guardian::create(['user_id' => $user->id, 'phone' => $data['phone']]);
        });

        return redirect()->route('admin.guardians.index')->with('success', 'Parent account created. Link children from the student edit page.');
    }

    public function edit(Guardian $guardian)
    {
        return view('admin.AdminGuardiansEdit', ['guardian' => $guardian->load('user')]);
    }

    public function update(Request $request, Guardian $guardian)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($guardian->user_id)],
            'password' => 'nullable|min:8',
            'phone' => 'required|max:20',
        ]);

        DB::transaction(function () use ($data, $guardian) {
            $user = $guardian->user;
            $user->name = $data['name'];
            $user->email = $data['email'];
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();
            $guardian->update(['phone' => $data['phone']]);
        });

        return redirect()->route('admin.guardians.index')->with('success', 'Parent updated successfully.');
    }

    public function destroy(Guardian $guardian)
    {
        $guardian->user->delete(); // students keep existing; their guardian_id becomes empty

        return redirect()->route('admin.guardians.index')->with('success', 'Parent deleted.');
    }
}
