@extends('layouts.LayoutApp')
@section('title', 'Class Students')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">{{ $schoolClass->full_name }} - students ({{ $students->count() }})</h3>
    <a href="{{ route('teacher.classes.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead><tr><th>Admission No</th><th>Name</th><th>Email</th><th>Parent</th></tr></thead>
        <tbody>
        @forelse ($students as $s)
            <tr>
                <td>{{ $s->admission_no }}</td>
                <td>{{ $s->user->name }}</td>
                <td>{{ $s->user->email }}</td>
                <td>{{ $s->guardian?->user?->name ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted">No students in this class.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
