@extends('Layouts.LayoutApp')
@section('title', $supplier ? 'Edit Supplier' : 'Add Supplier')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="mb-0">{{ $supplier ? 'Edit Supplier' : 'Add Supplier' }}</h3><a href="{{ route('procurement.suppliers.index') }}" class="btn btn-outline-secondary">Back</a></div>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm border-0"><div class="card-body">
    <form method="POST" action="{{ $supplier ? route('procurement.suppliers.update', $supplier) : route('procurement.suppliers.store') }}">
        @csrf @if ($supplier) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Supplier name <span class="text-danger">*</span></label><input name="name" @class(['form-control', 'is-invalid' => $errors->has('name')]) required maxlength="150" value="{{ old('name', $supplier?->name) }}">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Contact person</label><input name="contact_name" @class(['form-control', 'is-invalid' => $errors->has('contact_name')]) maxlength="150" value="{{ old('contact_name', $supplier?->contact_name) }}">@error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" @class(['form-control', 'is-invalid' => $errors->has('email')]) maxlength="255" value="{{ old('email', $supplier?->email) }}">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Phone</label><x-international-phone-input name="phone" :value="$supplier?->phone" /></div>
            <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3" maxlength="5000">{{ old('address', $supplier?->address) }}</textarea></div>
            <div class="col-12"><label class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $supplier?->is_active ?? true))> Active supplier</label></div>
        </div>
        <div class="mt-4"><button class="btn btn-primary">{{ $supplier ? 'Save Changes' : 'Create Supplier' }}</button> <a href="{{ route('procurement.suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
</div></div>
@endsection
