@include('layouts.LayoutErrors')
@php $isEdit = isset($student); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $student->user->name : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email (login)</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $isEdit ? $student->user->email : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ $isEdit ? '(leave blank to keep)' : '' }}</label>
        <input type="password" name="password" class="form-control" {{ $isEdit ? '' : 'required' }} minlength="8">
    </div>
    <div class="col-md-6">
        <label class="form-label">Admission no</label>
        <input type="text" name="admission_no" class="form-control" value="{{ old('admission_no', $isEdit ? $student->admission_no : '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Class</label>
        <select name="class_id" class="form-select" required>
            <option value="">Select class</option>
            @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected(old('class_id', $isEdit ? $student->class_id : '') == $c->id)>{{ $c->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Parent</label>
        <select name="guardian_id" class="form-select">
            <option value="">No parent linked</option>
            @foreach ($guardians as $g)
                <option value="{{ $g->id }}" @selected(old('guardian_id', $isEdit ? $student->guardian_id : '') == $g->id)>{{ $g->user->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Date of birth</label>
        <input type="date" name="dob" class="form-control" value="{{ old('dob', $isEdit ? $student->dob?->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $isEdit ? $student->phone : '') }}">
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
    <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
