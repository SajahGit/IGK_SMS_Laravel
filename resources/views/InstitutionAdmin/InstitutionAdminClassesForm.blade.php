@include('Layouts.LayoutErrors')
@php $isEdit = isset($schoolClass); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Class name (e.g. Grade 10)</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $schoolClass->name : '') }}" maxlength="50" pattern="[A-Za-z][A-Za-z0-9\s\-]*" data-live-filter="letter-start-class" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Section (e.g. A)</label>
        <input type="text" name="section" class="form-control" value="{{ old('section', $isEdit ? $schoolClass->section : '') }}" required maxlength="10" pattern="[A-Z0-9]+" data-live-filter="uppercase-section">
    </div>
    <div class="col-md-3">
        <label class="form-label">Capacity</label>
        <input type="number" name="capacity" class="form-control" min="1" max="200" step="1" value="{{ old('capacity', $isEdit ? $schoolClass->capacity : 40) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select">
            <option value="">Not linked to a program</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(old('program_id', $isEdit ? $schoolClass->program_id : '') == $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Academic year</label>
        <select name="academic_year_id" class="form-select">
            <option value="">Not linked to an academic year</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" @selected(old('academic_year_id', $isEdit ? $schoolClass->academic_year_id : '') == $academicYear->id)>{{ $academicYear->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Campus</label>
        <select name="branch_id" class="form-select" required>
            <option value="">Select campus</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $isEdit ? $schoolClass->branch_id : '') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
    <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
