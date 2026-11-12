@extends('Layouts.LayoutApp')
@section('title', 'Chart of Accounts')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Chart of Accounts</h3><small class="text-muted">General ledger account structure.</small></div>
    <a href="{{ route('admin.accounts.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Account</a>
</div>

@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="Search code or name" value="{{ request('q') }}"></div>
    <div class="col-md-3">
        <select name="type" class="form-select">
            <option value="">All types</option>
            @foreach (App\Models\Account::TYPES as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Type</th><th>Parent</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($accounts as $account)
                <tr>
                    <td><span class="badge bg-secondary">{{ $account->code }}</span></td>
                    <td>{{ $account->name }}</td>
                    <td>{{ App\Models\Account::TYPES[$account->type] ?? $account->type }}</td>
                    <td>{{ $account->parent?->name ?? '—' }}</td>
                    <td>@if ($account->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.accounting.ledger', ['account_id' => $account->id]) }}" class="btn btn-outline-secondary"><i class="bi bi-journal-text me-1"></i>Ledger</a>
                            <a href="{{ route('admin.accounts.edit', $account) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                            <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" class="d-inline" onsubmit="return confirm('Delete account {{ $account->code }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No accounts defined.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $accounts->links('pagination::bootstrap-5') }}</div>
@endsection
