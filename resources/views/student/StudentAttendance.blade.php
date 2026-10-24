@extends('layouts.LayoutApp')
@section('title', 'My Attendance')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">My attendance</h3>
    <form method="GET" class="d-flex gap-2">
        <input type="month" name="month" value="{{ $month }}" class="form-control">
        <button class="btn btn-outline-primary">Filter</button>
        @if ($month)<a href="{{ route('student.attendance') }}" class="btn btn-outline-secondary">Clear</a>@endif
    </form>
</div>
@include('layouts.LayoutAttendanceTable')
@endsection
