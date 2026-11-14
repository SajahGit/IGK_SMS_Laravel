@include('Layouts.LayoutErrors')
@php $isEdit = isset($subject); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Subject name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $subject->name : '') }}" maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x2d]*" data-live-filter="letter-start-name" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Code (e.g. MATH)</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $isEdit ? $subject->code : '') }}" required maxlength="15" pattern="[A-Z][A-Z0-9\-]*" data-live-filter="uppercase-subject-code">
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
    <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
