@extends('Layouts.LayoutApp')
@section('title', $branch->exists ? 'Edit Branch' : 'Create Branch')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{{ $branch->exists ? 'Edit Branch' : 'Create Branch' }}</h3>
    <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}">
            @csrf
            @if ($branch->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Branch Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name', $branch->name) }}"></div>
                <div class="col-md-3"><label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required maxlength="20" value="{{ old('code', $branch->code) }}"></div>
                <div class="col-md-3"><label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $branch->city) }}"></div>
                <div class="col-md-12"><label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address', $branch->address) }}"></div>
                <div class="col-md-4"><label class="form-label">Principal</label>
                    <input type="text" name="principal_name" class="form-control" value="{{ old('principal_name', $branch->principal_name) }}"></div>
                <div class="col-md-4"><label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $branch->phone) }}"></div>
                <div class="col-md-4"><label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $branch->email) }}"></div>
                <div class="col-md-3"><div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_main" value="1" @checked(old('is_main', $branch->is_main))>
                    <label class="form-check-label">Main campus</label></div></div>
                <div class="col-md-3"><div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $branch->is_active))>
                    <label class="form-check-label">Active</label></div></div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $branch->exists ? 'Update Branch' : 'Create Branch' }}</button>
                <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
