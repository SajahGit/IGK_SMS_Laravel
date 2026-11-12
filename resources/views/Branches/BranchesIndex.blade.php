@extends('Layouts.LayoutApp')
@section('title', 'Branches')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Branches / Campuses</h3>
        <small class="text-muted">Manage multiple campuses and view consolidated reporting.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.accounting.branch-summary') }}" class="btn btn-outline-secondary"><i class="bi bi-bar-chart-line me-1"></i>Consolidated Summary</a>
        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Branch</a>
    </div>
</div>

@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>Name</th><th>Code</th><th>City</th><th>Principal</th><th>Phone</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @forelse ($branches as $branch)
                <tr>
                    <td>{{ $branch->name }} @if ($branch->is_main)<span class="badge bg-primary ms-1">Main</span>@endif</td>
                    <td><span class="badge bg-secondary">{{ $branch->code }}</span></td>
                    <td>{{ $branch->city ?? '—' }}</td>
                    <td>{{ $branch->principal_name ?? '—' }}</td>
                    <td>{{ $branch->phone ?? '—' }}</td>
                    <td>@if ($branch->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                            <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" class="d-inline" onsubmit="return confirm('Delete branch {{ $branch->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No branches defined.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $branches->links('pagination::bootstrap-5') }}</div>
@endsection
