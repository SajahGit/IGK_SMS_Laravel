@include('Layouts.LayoutErrors')
@php
    $isEdit = isset($schoolClass);
    $selectedBranchId = old('branch_id', $isEdit ? $schoolClass->branch_id : \App\Support\BranchContext::activeBranchId());
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Class name (e.g. Grade 10) <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $schoolClass->name : '') }}" maxlength="50" pattern="[A-Za-z][A-Za-z0-9\s\-]*" data-live-filter="letter-start-class" placeholder="e.g. Grade 10" required>
        <div class="form-text small">Must start with a letter (letters, numbers, hyphens, and spaces allowed).</div>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Section (e.g. A) <span class="text-danger">*</span></label>
        <input type="text" name="section" class="form-control" value="{{ old('section', $isEdit ? $schoolClass->section : '') }}" required maxlength="10" pattern="[A-Z0-9]+" data-live-filter="uppercase-section" placeholder="e.g. A">
        <div class="form-text small">Uppercase letters and digits only.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Student Capacity <span class="text-danger">*</span></label>
        <input type="number" name="capacity" class="form-control" min="1" max="200" step="1" value="{{ old('capacity', $isEdit ? $schoolClass->capacity : 40) }}" required>
        <div class="form-text small">Maximum student capacity (1-200).</div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Campus / Branch <span class="text-danger">*</span></label>
        <select name="branch_id" class="form-select" required>
            <option value="">Select campus</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>{{ $branch->name }} ({{ $branch->code }})</option>
            @endforeach
        </select>
        <div class="form-text small">The branch this class belongs to.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Program</label>
        <select name="program_id" class="form-select">
            <option value="">Not linked to a program</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(old('program_id', $isEdit ? $schoolClass->program_id : '') == $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
        <div class="form-text small">Optional academic stream or program.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Academic Year</label>
        <select name="academic_year_id" class="form-select">
            <option value="">Not linked to an academic year</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" @selected(old('academic_year_id', $isEdit ? $schoolClass->academic_year_id : ($academicYear->is_current ? $academicYear->id : '')) == $academicYear->id)>{{ $academicYear->name }} {{ $academicYear->is_current ? '(Current)' : '' }}</option>
            @endforeach
        </select>
        <div class="form-text small">Active academic session.</div>
    </div>
</div>
<div class="mt-4 pt-2 border-top d-flex align-items-center gap-2">
    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>{{ $isEdit ? 'Update Class' : 'Save Class' }}</button>
    <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
