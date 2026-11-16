@extends('Layouts.LayoutApp')
@section('title', 'Edit Profile')
@section('content')
<h3 class="mb-3">Edit profile</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('student.profile.update') }}">
        @csrf @method('PUT')
        @include('Layouts.LayoutErrors')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" data-person-name @class(['form-control', 'is-invalid' => $errors->has('name')]) value="{{ old('name', $student->user->name) }}" required>
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <x-international-phone-input name="phone" :value="$student->phone" />
            </div>
            <div class="col-12">
                <div class="alert alert-light border small text-muted d-flex align-items-center mb-0">
                    <i class="bi bi-shield-lock me-2 text-primary fs-5"></i>
                    <div>
                        <strong>Password Policy:</strong> Student passwords can only be changed or reset by the School Administrator. If you need a new password, please contact the school office.
                    </div>
                </div>
            </div>
        </div>
        <p class="text-muted small mt-3 mb-0">Password, class, admission number and date of birth can only be changed by the administrator.</p>
        <div class="mt-3">
            <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
            <a href="{{ route('student.profile') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div></div>
@endsection
