@include('layouts.LayoutErrors')
@php $isEdit = isset($guardian); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $guardian->user->name : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email (login)</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $isEdit ? $guardian->user->email : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ $isEdit ? '(leave blank to keep)' : '' }}</label>
        <input type="password" name="password" class="form-control" {{ $isEdit ? '' : 'required' }} minlength="8">
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $isEdit ? $guardian->phone : '') }}" required>
    </div>
</div>
<p class="text-muted small mt-3 mb-0">To link children to this parent, choose the parent on the student's edit page.</p>
<div class="mt-3">
    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
    <a href="{{ route('admin.guardians.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
