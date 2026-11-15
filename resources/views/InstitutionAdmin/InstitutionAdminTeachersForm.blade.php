@include('Layouts.LayoutErrors')
@php
    $isEdit = isset($teacher);
    $selectedBranchId = old('branch_id', $isEdit ? ($teacher->branch_id ?? $teacher->user->branch_id) : \App\Support\BranchContext::activeBranchId());
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Full name <span class="text-danger">*</span></label>
        <input type="text" name="name" data-person-name maxlength="100" autocomplete="off" @class(['form-control', 'is-invalid' => $errors->has('name')]) value="{{ old('name', $isEdit ? $teacher->user->name : '') }}" required>
        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Email (login) <span class="text-danger">*</span></label>
        <input type="email" name="email" autocomplete="off" data-lpignore="true" data-1p-ignore="true" @class(['form-control', 'is-invalid' => $errors->has('email')]) value="{{ old('email', $isEdit ? $teacher->user->email : '') }}" required>
        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Password {{ $isEdit ? '(leave blank to keep current)' : '' }} <span class="{{ $isEdit ? '' : 'text-danger' }}">*</span></label>
        <input type="password" name="password" autocomplete="new-password" @class(['form-control', 'is-invalid' => $errors->has('password')]) {{ $isEdit ? '' : 'required' }} minlength="8" maxlength="32">
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Phone</label>
        <x-international-phone-input name="phone" :value="$isEdit ? $teacher->phone : ''" />
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Campus / Branch <span class="text-danger">*</span></label>
        <select name="branch_id" class="form-select" required>
            <option value="">Select campus</option>
            @foreach ($branches ?? [] as $b)
                <option value="{{ $b->id }}" @selected((string) $selectedBranchId === (string) $b->id)>{{ $b->name }} ({{ $b->code }})</option>
            @endforeach
        </select>
        <div class="form-text small">Assigned campus for this teacher.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Department</label>
        <select name="department_id" class="form-select" data-searchable-select>
            <option value="">Not assigned</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $isEdit ? $teacher->department_id : '') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        <div class="form-text small">Faculty / academic department.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On leave'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $isEdit ? $teacher->status : 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text small">Employment status.</div>
    </div>
</div>
<div class="mt-4 pt-2 border-top d-flex align-items-center gap-2">
    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>{{ $isEdit ? 'Update Teacher' : 'Save Teacher' }}</button>
    <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
