@include('Layouts.LayoutErrors')
@php $isEdit = isset($teacher); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $teacher->user->name : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email (login)</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $isEdit ? $teacher->user->email : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ $isEdit ? '(leave blank to keep)' : '' }}</label>
        <input type="password" name="password" class="form-control" {{ $isEdit ? '' : 'required' }} minlength="10">
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $isEdit ? $teacher->phone : '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select">
            <option value="">Not assigned</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $isEdit ? $teacher->department_id : '') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On leave'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $isEdit ? $teacher->status : 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
    <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
