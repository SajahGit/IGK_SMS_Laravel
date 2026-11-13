<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Rules\ValidInternationalPhone;
use App\Rules\ValidPersonName;
use App\Support\AuditLogger;
use App\Support\InternationalPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = Branch::orderByDesc('is_main')->orderBy('name')->paginate(10)->withQueryString();

        return view('Branches.BranchesIndex', compact('branches'));
    }

    public function switchContext(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->isInstitutionAdmin(), 403);

        if ($request->input('branch_id') === 'all') {
            abort_unless($request->user()->isSuperAdmin(), 403);
            $request->session()->put('active_branch_id', 'all');
            $request->session()->put('branch_context_initialized', true);

            return back()->with('success', 'All campuses are now in view.');
        }

        $data = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)->whereNull('deleted_at')],
        ]);

        $request->session()->put('active_branch_id', (int) $data['branch_id']);
        $request->session()->put('branch_context_initialized', true);

        return back()->with('success', 'Active campus changed successfully.');
    }

    public function create(): View
    {
        return view('Branches.BranchesForm', ['branch' => new Branch(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $branch = Branch::create($data);
        $this->enforceSingleMain($branch);
        AuditLogger::log('created_branch', $branch, null, $branch->toArray());

        return redirect()->route('admin.branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch): View
    {
        return view('Branches.BranchesForm', compact('branch'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $before = $branch->toArray();
        $branch->update($this->validated($request, $branch));
        $this->enforceSingleMain($branch);
        AuditLogger::log('updated_branch', $branch, $before, $branch->toArray());

        return redirect()->route('admin.branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->is_main) {
            return back()->withErrors(['branch' => 'The main branch cannot be deleted.']);
        }
        $before = $branch->toArray();
        $branch->delete();
        AuditLogger::log('deleted_branch', $branch, $before, null);

        return redirect()->route('admin.branches.index')->with('success', 'Branch deleted.');
    }

    private function validated(Request $request, ?Branch $branch = null): array
    {
        InternationalPhoneNumber::normalizeRequest($request);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($branch?->id)],
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:16', Rule::unique('branches', 'phone')->ignore($branch?->id), new ValidInternationalPhone($request->input('phone_region'))],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('branches', 'email')->ignore($branch?->id)],
            'principal_name' => ['nullable', 'string', 'max:120', new ValidPersonName],
        ]);
        $data['is_main'] = $request->boolean('is_main');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function enforceSingleMain(Branch $branch): void
    {
        if ($branch->is_main) {
            Branch::where('id', '!=', $branch->id)->update(['is_main' => false]);
        }
    }
}
