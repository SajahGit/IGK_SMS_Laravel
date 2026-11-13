@include('Layouts.LayoutErrors')
@if (session()->has('possible_duplicates'))
    <div class="alert alert-warning" role="alert">
        <strong>Possible duplicate student</strong>
        <p class="mb-2">Review these matches. If this is a different person, confirm the new admission; otherwise cancel and open the existing record.</p>
        <ul class="mb-0">
            @foreach (session('possible_duplicates') as $duplicate)
                <li>{{ $duplicate['name'] }} — {{ $duplicate['admission_no'] }} — {{ $duplicate['class'] }} — DOB {{ $duplicate['dob'] }}</li>
            @endforeach
        </ul>
    </div>
@endif
@php $isEdit = isset($student); @endphp
<div class="row g-3" data-student-form>
@unless ($isEdit)
<div class="col-12">
    <label for="existing_user_id" class="form-label">Existing student login (optional)</label>
    <select name="existing_user_id" id="existing_user_id" class="form-select">
        <option value="">Create a new login</option>
        @foreach ($unlinkedStudentUsers as $existingUser)
            <option value="{{ $existingUser->id }}" @selected(old('existing_user_id') == $existingUser->id)>
                {{ $existingUser->name }} — {{ $existingUser->email }}
            </option>
        @endforeach
    </select>
    <div class="form-text">Choose this to connect an existing Student account that currently has no student profile.</div>
    @if ($unlinkedStudentUsers->isEmpty())
        <div class="form-text">There are no unlinked Student accounts to repair.</div>
    @endif
</div>
@endunless
    <div class="col-12" id="student-login-fields">
        <div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full name <span class="text-danger">*</span></label>
        <input type="text" name="name" data-person-name maxlength="100" autocomplete="off" @class(['form-control', 'is-invalid' => $errors->has('name')]) value="{{ old('name', $isEdit ? $student->user->name : '') }}" required>
        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Email (login) <span class="text-danger">*</span></label>
        <input type="email" name="email" autocomplete="off" data-lpignore="true" data-1p-ignore="true" @class(['form-control', 'is-invalid' => $errors->has('email')]) value="{{ old('email', $isEdit ? $student->user->email : '') }}" required>
        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ $isEdit ? '(leave blank to keep current)' : '' }} <span class="{{ $isEdit ? '' : 'text-danger' }}">*</span></label>
        <input type="password" name="password" autocomplete="new-password" @class(['form-control', 'is-invalid' => $errors->has('password')]) {{ $isEdit ? '' : 'required' }} minlength="8" maxlength="32" placeholder="8-32 characters with upper, lower, and number">
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Admission number <span class="text-danger">*</span></label>
        <input type="text" name="admission_no" class="form-control" value="{{ old('admission_no', $isEdit ? $student->admission_no : '') }}" placeholder="Auto-generated from academic year and program">
        <div class="form-text small text-muted">Leave blank to generate the next year/program-specific number.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Class & Section <span class="text-danger">*</span></label>
        <select name="class_id" @class(['form-select', 'is-invalid' => $errors->has('class_id')]) data-searchable-select required>
            <option value="">Select class</option>
            @foreach ($classes as $c)
                @php
                    $isFull = $c->students_count >= $c->capacity && (!$isEdit || $student->class_id !== $c->id);
                @endphp
                <option value="{{ $c->id }}" @selected(old('class_id', $isEdit ? $student->class_id : '') == $c->id) {{ $isFull ? 'disabled' : '' }}>
                    {{ $c->full_name }} ({{ $c->students_count }}/{{ $c->capacity }} enrolled) {{ $isFull ? '— FULL' : '' }}
                </option>
            @endforeach
        </select>
        @error('class_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select">
            <option value="">Use the selected class program</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(old('program_id', $isEdit ? $student->program_id : '') == $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Academic year</label>
        <select name="academic_year_id" class="form-select">
            <option value="">Use the selected class academic year</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" @selected(old('academic_year_id', $isEdit ? $student->academic_year_id : '') == $academicYear->id)>{{ $academicYear->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Guardians <span class="text-danger">*</span></label>
        <x-guardian-selector
            name="guardian_ids[]"
            :guardians="$guardians"
            :selected="old('guardian_ids', $isEdit ? $student->guardians->pluck('id')->all() : [])"
            multiple
        />
    </div>
    <div class="col-md-4">
        <label class="form-label">Primary contact</label>
        <x-guardian-selector
            name="primary_guardian_id"
            :guardians="$guardians"
            :selected="old('primary_guardian_id', $isEdit ? $student->primaryGuardian->first()?->id : '')"
            allow-none
        />
    </div>
    <div class="col-md-4">
        <label class="form-label">Date of birth</label>
        <input type="date" name="dob" class="form-control" value="{{ old('dob', $isEdit ? $student->dob?->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
            <option value="">Not specified</option>
            @foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                <option value="{{ $value }}" @selected(old('gender', $isEdit ? $student->gender : '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
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
    <div class="col-md-4">
        <label class="form-label">Admission date</label>
        <input type="date" name="admission_date" class="form-control" max="{{ today()->toDateString() }}" value="{{ old('admission_date', $isEdit ? $student->admission_date?->format('Y-m-d') : today()->toDateString()) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <x-international-phone-input name="phone" :value="$isEdit ? $student->phone : ''" />
    </div>
    @if ($isEdit)
        <div class="col-md-6">
            <label class="form-label">Student status</label>
            <select name="status" class="form-select" required>
                @foreach (['active' => 'Active', 'suspended' => 'Suspended', 'graduated' => 'Graduated', 'withdrawn' => 'Withdrawn'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $student->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif
</div>
@unless ($isEdit)
<script>
    const existingStudentAccount = document.getElementById('existing_user_id');
    const studentLoginFields = document.getElementById('student-login-fields');

    function toggleStudentLoginFields() {
        const useExistingAccount = existingStudentAccount.value !== '';
        studentLoginFields.hidden = useExistingAccount;
        studentLoginFields.querySelectorAll('input').forEach((input) => {
            input.disabled = useExistingAccount;
            input.required = !useExistingAccount;
        });
        studentLoginFields.querySelectorAll('[data-document-number]').forEach((input) => {
            const form = input.form;
            input.disabled = useExistingAccount
                || !form.querySelector('[data-document-type]').value
                || !form.querySelector('[data-country-value]').value;
        });
    }

    existingStudentAccount.addEventListener('change', toggleStudentLoginFields);
    toggleStudentLoginFields();
</script>
@endunless
<div class="mt-4">
    @if (session()->has('possible_duplicates'))
        <button class="btn btn-warning" name="confirm_duplicate" value="1"><i class="bi bi-person-check me-1"></i>Confirm Different Student and Continue</button>
    @else
        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save Student</button>
    @endif
    <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
