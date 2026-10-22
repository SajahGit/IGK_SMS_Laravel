@include('layouts.LayoutErrors')
@php $isEdit = isset($subject); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Subject name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $subject->name : '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Code (e.g. MATH)</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $isEdit ? $subject->code : '') }}" required maxlength="10">
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
    <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
