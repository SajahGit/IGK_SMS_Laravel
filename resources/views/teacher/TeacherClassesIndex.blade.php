@extends('layouts.LayoutApp')
@section('title', 'My Classes')
@section('content')
<h3 class="mb-3">My classes</h3>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Class</th><th>Subject</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($assignments as $a)
            <tr>
                <td>{{ $a->schoolClass->full_name }}</td>
                <td>{{ $a->subject->name }}</td>
                <td class="text-end">
                    <a href="{{ route('teacher.classes.students', $a->schoolClass) }}" class="btn btn-sm btn-outline-primary">Students</a>
                    <a href="{{ route('teacher.attendance.create', ['class_id' => $a->class_id]) }}" class="btn btn-sm btn-outline-success">Mark attendance</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted">No classes assigned yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
