@include('layouts.LayoutErrors')
@php $isEdit = isset($schoolClass); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Class name (e.g. Grade 10)</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $schoolClass->name : '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Section (e.g. A)</label>
        <input type="text" name="section" class="form-control" value="{{ old('section', $isEdit ? $schoolClass->section : '') }}" required maxlength="5">
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
    <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
