<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use App\Rules\ValidInternationalPhone;
use App\Rules\ValidPersonName;
use App\Services\BranchTransferService;
use App\Support\BranchContext;
use App\Support\InternationalPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::withoutGlobalScope('assigned-branch')->where('is_active', true)->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        $teachers = Teacher::with([
            'user' => fn ($q) => $q->withoutGlobalScopes(),
            'user.branch' => fn ($q) => $q->withoutGlobalScopes(),
            'department',
            'branch' => fn ($q) => $q->withoutGlobalScopes(),
            'branchTransfers.fromBranch' => fn ($q) => $q->withoutGlobalScopes(),
            'branchTransfers.toBranch' => fn ($q) => $q->withoutGlobalScopes(),
            'classSubjects' => fn ($query) => $query->withoutGlobalScopes()->whereHas('schoolClass', fn ($q) => $q->withoutGlobalScopes())->whereHas('subject'),
            'classSubjects.schoolClass' => fn ($query) => $query->withoutGlobalScopes(),
            'classSubjects.schoolClass.branch' => fn ($query) => $query->withoutGlobalScopes(),
            'classSubjects.subject',
        ])
            ->when($request->q, fn ($query, $s) => $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->orderByDesc('updated_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('InstitutionAdmin.InstitutionAdminTeachersIndex', compact('teachers', 'branches', 'departments'));
    }

    public function create()
    {
        return view('InstitutionAdmin.InstitutionAdminTeachersCreate', [
            'departments' => Department::orderBy('name')->get(),
            'branches' => Branch::withoutGlobalScope('assigned-branch')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        InternationalPhoneNumber::normalizeRequest($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new ValidPersonName],
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('teachers', 'phone'), new ValidInternationalPhone($request->input('phone_region'))],
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => ['nullable', 'exists:branches,id'],
            'status' => 'required|in:active,inactive,on_leave',
        ]);

        DB::transaction(function () use ($data) {
            $branchId = $data['branch_id'] ?? BranchContext::activeBranchId();
            if (! $branchId) {
                $branchId = Branch::withoutGlobalScope('assigned-branch')->where('is_active', true)->orderByDesc('is_main')->value('id');
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'teacher',
                'branch_id' => $branchId,
                'must_change_password' => true,
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'phone' => $data['phone'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'status' => $data['status'],
            ]);

            \App\Services\EmployeeSyncService::ensureForTeacher($teacher);
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher created successfully with campus assignment.');
    }

    public function edit(Teacher $teacher)
    {
        return view('InstitutionAdmin.InstitutionAdminTeachersEdit', [
            'teacher' => $teacher->load([
                'user' => fn ($q) => $q->withoutGlobalScopes(),
                'user.branch' => fn ($q) => $q->withoutGlobalScopes(),
                'branch' => fn ($q) => $q->withoutGlobalScopes(),
            ]),
            'departments' => Department::orderBy('name')->get(),
            'branches' => Branch::withoutGlobalScope('assigned-branch')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        InternationalPhoneNumber::normalizeRequest($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', new ValidPersonName],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($teacher->user_id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('teachers', 'phone')->ignore($teacher), new ValidInternationalPhone($request->input('phone_region'))],
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => ['nullable', 'exists:branches,id'],
            'status' => 'required|in:active,inactive,on_leave',
        ]);

        DB::transaction(function () use ($data, $teacher) {
            $branchId = $data['branch_id'] ?? $teacher->branch_id ?? $teacher->user->branch_id;

            $user = $teacher->user;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->is_active = $data['status'] !== 'inactive';
            if ($branchId) {
                $user->branch_id = $branchId;
            }
            if (! empty($data['password'])) {
                $user->password = $data['password'];
                $user->must_change_password = true;
            }
            $user->save();

            $teacher->update([
                'phone' => $data['phone'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'branch_id' => $branchId,
                'status' => $data['status'],
            ]);
            $teacher->touch();

            // Synchronize linked Employee and StaffAttendance records
            $employee = \App\Models\Employee::withoutGlobalScopes()
                ->where(function ($q) use ($teacher) {
                    $q->where('teacher_id', $teacher->id)
                        ->orWhere('user_id', $teacher->user_id);
                })
                ->first();

            if ($employee) {
                $employee->update([
                    'department_id' => $data['department_id'] ?? $employee->department_id,
                    'branch_id' => $branchId ?? $employee->branch_id,
                    'employment_status' => match ($data['status']) {
                        'active' => 'active',
                        'on_leave' => 'on_leave',
                        'inactive' => 'terminated',
                        default => $employee->employment_status,
                    },
                ]);
            }

            \App\Models\StaffAttendance::query()
                ->where('teacher_id', $teacher->id)
                ->update(['staff_name' => $data['name']]);
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher updated successfully.');
    }

    public function show(Teacher $teacher)
    {
        return app(\App\Http\Controllers\Teacher\ProfileController::class)->renderProfileView($teacher, false);
    }

    public function transfer(Request $request, Teacher $teacher)
    {
        abort_unless($request->user()->can('staff.update') || $request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'to_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'transfer_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        BranchTransferService::transferTeacher(
            $teacher,
            (int) $data['to_branch_id'],
            $data['transfer_date'],
            $data['reason'] ?? null,
            $request->user()->id
        );

        return back()->with('success', 'Teacher transferred to new campus successfully.');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->user->delete(); // also removes the teacher's class-subject assignments

        return redirect()->route('admin.teachers.index')->with('success', 'Teacher deleted.');
    }
}
