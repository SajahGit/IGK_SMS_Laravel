@include('Layouts.LayoutErrors')
@php $isEdit = isset($student); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full name</label>
        <input type="text" name="name" data-person-name maxlength="100" autocomplete="off" @class(['form-control', 'is-invalid' => $errors->has('name')]) value="{{ old('name', $isEdit ? $student->user->name : '') }}" required>
        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Email (login)</label>
        <input type="email" name="email" autocomplete="off" data-lpignore="true" data-1p-ignore="true" @class(['form-control', 'is-invalid' => $errors->has('email')]) value="{{ old('email', $isEdit ? $student->user->email : '') }}" required>
        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ $isEdit ? '(leave blank to keep)' : '' }}</label>
        <input type="password" name="password" autocomplete="new-password" @class(['form-control', 'is-invalid' => $errors->has('password')]) {{ $isEdit ? '' : 'required' }} minlength="8" maxlength="32">
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Admission no</label>
        <input type="text" name="admission_no" class="form-control" value="{{ old('admission_no', $isEdit ? $student->admission_no : '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Class</label>
        <select name="class_id" @class(['form-select', 'is-invalid' => $errors->has('class_id')]) data-searchable-select required>
            <option value="">Select class</option>
            @foreach ($classes as $c)
                @php $isFull = $c->students_count >= $c->capacity && (!$isEdit || $student->class_id !== $c->id); @endphp
                <option value="{{ $c->id }}" @selected(old('class_id', $isEdit ? $student->class_id : '') == $c->id) {{ $isFull ? 'disabled' : '' }}>
                    {{ $c->full_name }} ({{ $c->students_count }}/{{ $c->capacity }} enrolled) {{ $isFull ? '— FULL' : '' }}
                </option>
            @endforeach
        </select>
        @error('class_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Guardian</label>
        <x-guardian-selector
            name="guardian_id"
            :guardians="$guardians"
            :selected="old('guardian_id', $isEdit ? $student->guardian_id : '')"
            allow-none
        />
    </div>
    <div class="col-md-4">
        <label class="form-label">Date of birth</label>
        <input type="date" name="dob" class="form-control" value="{{ old('dob', $isEdit ? $student->dob?->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Nationality</label>
        <x-international-nationality-select :value="old('nationality', $isEdit ? $student->nationality : '')" />
    </div>
    <div class="col-md-4">
        <label class="form-label">Document type <span class="text-danger">*</span></label>
        <select name="document_type" data-document-type @class(['form-select', 'is-invalid' => $errors->has('document_type')]) required>
            <option value="">Select document type</option>
            @foreach (['national_id' => 'National ID', 'passport' => 'Passport'] as $value => $label)
                <option value="{{ $value }}" @selected(old('document_type', $isEdit ? $student->document_type : '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('document_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Issuing country <span class="text-danger">*</span></label>
        <x-international-country-select name="issuing_country" :value="old('issuing_country', $isEdit ? $student->issuing_country : '')" required />
    </div>
    <div class="col-md-4">
        <label class="form-label">Document number <span class="text-danger">*</span></label>
        @php
            $documentTypeValue = old('document_type', $isEdit ? $student->document_type : '');
            $issuingCountryValue = old('issuing_country', $isEdit ? $student->issuing_country : '');
        @endphp
        <input type="text" name="document_number" data-document-number @class(['form-control', 'is-invalid' => $errors->has('document_number')]) value="{{ old('document_number', $isEdit ? $student->document_number : '') }}" maxlength="20" autocomplete="off" {{ $documentTypeValue && $issuingCountryValue ? '' : 'disabled' }} required>
        @error('document_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <x-international-phone-input name="phone" :value="$isEdit ? $student->phone : ''" />
    </div>
</div>
<div class="mt-4">
    <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
    <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
