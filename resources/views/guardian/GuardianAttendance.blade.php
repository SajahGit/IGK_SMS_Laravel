@extends('layouts.LayoutApp')
@section('title', 'Child Attendance')
@section('content')
@include('guardian.GuardianChildSwitch', ['routeName' => 'guardian.attendance'])
<form method="GET" class="d-flex gap-2 mb-3" style="max-width: 360px;">
    <input type="month" name="month" value="{{ $month }}" class="form-control">
    <button class="btn btn-outline-primary">Filter</button>
    @if ($month)<a href="{{ route('guardian.attendance', $student) }}" class="btn btn-outline-secondary">Clear</a>@endif
</form>
@include('layouts.LayoutAttendanceTable')
@endsection
