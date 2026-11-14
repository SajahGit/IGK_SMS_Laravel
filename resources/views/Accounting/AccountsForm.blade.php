@extends('Layouts.LayoutApp')
@section('title', $account->exists ? 'Edit Account' : 'Create Account')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{{ $account->exists ? 'Edit Account' : 'Create Account' }}</h3>
    <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ $account->exists ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}">
            @csrf
            @if ($account->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required maxlength="20" pattern="[A-Z0-9\x2d]+" data-live-filter="uppercase-code" value="{{ old('code', $account->code) }}"></div>
                <div class="col-md-5"><label class="form-label">Account Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x26\x2d]*" data-live-filter="letter-start-account" value="{{ old('name', $account->name) }}"></div>
                <div class="col-md-4"><label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        @foreach (App\Models\Account::TYPES as $key => $label)<option value="{{ $key }}" @selected(old('type', $account->type) === $key)>{{ $label }}</option>@endforeach
                    </select></div>
                <div class="col-md-6"><label class="form-label">Parent Account</label>
                    <select name="parent_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach ($parents as $p)<option value="{{ $p->id }}" @selected((string) old('parent_id', $account->parent_id) === (string) $p->id)>{{ $p->code }} — {{ $p->name }}</option>@endforeach
                    </select></div>
                <div class="col-md-12"><label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" maxlength="255" data-live-filter="account-description">{{ old('description', $account->description) }}</textarea></div>
                <div class="col-md-3"><div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $account->is_active))>
                    <label class="form-check-label">Active</label></div></div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $account->exists ? 'Update Account' : 'Create Account' }}</button>
                <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
