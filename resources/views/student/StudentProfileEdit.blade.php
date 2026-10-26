@extends('layouts.LayoutApp')
@section('title', 'Edit Profile')
@section('content')
<h3 class="mb-3">Edit profile</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('student.profile.update') }}">
        @csrf @method('PUT')
        @include('layouts.LayoutErrors')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $student->user->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $student->phone) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">New password (leave blank to keep)</label>
                <input type="password" name="password" class="form-control" minlength="8">
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm new password</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
        </div>
        <p class="text-muted small mt-3 mb-0">Class, admission number and date of birth can only be changed by the admin.</p>
        <div class="mt-3">
            <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
            <a href="{{ route('student.profile') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div></div>
@endsection
