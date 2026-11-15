@include('Layouts.LayoutErrors')
@php
    $isEdit = isset($schoolClass);
    $selectedBranchId = old('branch_id', $isEdit ? $schoolClass->branch_id : \App\Support\BranchContext::activeBranchId());
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Class name (e.g. Grade 10) <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $schoolClass->name : '') }}" maxlength="50" pattern="[A-Za-z][A-Za-z0-9\s\-]*" placeholder="e.g. Grade 10" required>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Section (e.g. A) <span class="text-danger">*</span></label>
        <input type="text" name="section" class="form-control" value="{{ old('section', $isEdit ? $schoolClass->section : '') }}" required maxlength="10" pattern="[A-Z0-9]+" placeholder="e.g. A">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Capacity <span class="text-danger">*</span></label>
        <input type="number" name="capacity" class="form-control" min="1" max="200" step="1" value="{{ old('capacity', $isEdit ? $schoolClass->capacity : 40) }}" required>
    </div>
</div>
<div class="mt-4 pt-2 border-top d-flex align-items-center gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
    <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
