@extends('layouts.LayoutApp')
@section('title', 'Class Subjects')
@section('content')
<h3 class="mb-3">Class subjects and teachers</h3>
@include('layouts.LayoutErrors')

<div class="card shadow-sm mb-4"><div class="card-body">
    <form method="POST" action="{{ route('admin.class-subjects.store') }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-3">
            <label class="form-label">Class</label>
            <select name="class_id" class="form-select" required>
                <option value="">Select class</option>
                @foreach ($classes as $c)
                    <option value="{{ $c->id }}" @selected(old('class_id') == $c->id)>{{ $c->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-select" required>
                <option value="">Select subject</option>
                @foreach ($subjects as $s)
                    <option value="{{ $s->id }}" @selected(old('subject_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Teacher</label>
            <select name="teacher_id" class="form-select" required>
                <option value="">Select teacher</option>
                @foreach ($teachers as $t)
                    <option value="{{ $t->id }}" @selected(old('teacher_id') == $t->id)>{{ $t->user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100">Assign</button></div>
    </form>
</div></div>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Class</th><th>Subject</th><th>Teacher</th><th class="text-end">Action</th></tr></thead>
        <tbody>
        @forelse ($assignments as $a)
            <tr>
                <td>{{ $a->schoolClass->full_name }}</td>
                <td>{{ $a->subject->name }}</td>
                <td>{{ $a->teacher->user->name }}</td>
                <td class="text-end">
                    <form method="POST" action="{{ route('admin.class-subjects.destroy', $a) }}" class="d-inline" onsubmit="return confirm('Remove this assignment?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Remove</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted">No assignments yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
