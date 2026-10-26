@extends('layouts.LayoutApp')
@section('title', 'My Profile')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">My profile</h3>
    <a href="{{ route('student.profile.edit') }}" class="btn btn-primary">Edit profile</a>
</div>
<div class="card shadow-sm"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $student->user->name }}</dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $student->user->email }}</dd>
        <dt class="col-sm-3">Admission no</dt><dd class="col-sm-9">{{ $student->admission_no }}</dd>
        <dt class="col-sm-3">Class</dt><dd class="col-sm-9">{{ $student->class->full_name }}</dd>
        <dt class="col-sm-3">Date of birth</dt><dd class="col-sm-9">{{ $student->dob?->format('d M Y') ?? '-' }}</dd>
        <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $student->phone ?? '-' }}</dd>
        <dt class="col-sm-3">Parent</dt><dd class="col-sm-9">{{ $student->guardian?->user?->name ?? '-' }}</dd>
    </dl>
</div></div>
@endsection
