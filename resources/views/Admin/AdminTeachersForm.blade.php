@include('Layouts.LayoutErrors')
@php $isEdit = isset($teacher); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full name</label>
        <input type="text" name="name" data-person-name maxlength="100" autocomplete="off" @class(['form-control', 'is-invalid' => $errors->has('name')]) value="{{ old('name', $isEdit ? $teacher->user->name : '') }}" required>
        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Email (login)</label>
        <input type="email" name="email" autocomplete="off" data-lpignore="true" data-1p-ignore="true" @class(['form-control', 'is-invalid' => $errors->has('email')]) value="{{ old('email', $isEdit ? $teacher->user->email : '') }}" required>
        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ $isEdit ? '(leave blank to keep)' : '' }}</label>
        <input type="password" name="password" autocomplete="new-password" @class(['form-control', 'is-invalid' => $errors->has('password')]) {{ $isEdit ? '' : 'required' }} minlength="8" maxlength="32">
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <x-international-phone-input name="phone" :value="$isEdit ? $teacher->phone : ''" />
    </div>
    <div class="col-md-6">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select" data-searchable-select>
            <option value="">Not assigned</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $isEdit ? $teacher->department_id : '') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
    <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
