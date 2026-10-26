@extends('layouts.LayoutApp')
@section('title', 'Mark Attendance')
@section('content')
<h3 class="mb-3">Mark attendance</h3>
@include('layouts.LayoutErrors')

<form method="GET" action="{{ route('teacher.attendance.create') }}" class="row g-2 mb-4">
    <div class="col-md-4">
        <select name="class_id" class="form-select" required>
            <option value="">Select class</option>
            @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><input type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}" class="form-control" required></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Load students</button></div>
</form>

@if ($classId)
    <form method="POST" action="{{ route('teacher.attendance.store') }}">
        @csrf
        <input type="hidden" name="class_id" value="{{ $classId }}">
        <input type="hidden" name="date" value="{{ $date }}">

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <span>Date: <strong>{{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}</strong>
                    @if ($existing->isNotEmpty()) <span class="badge bg-info text-dark ms-2">Already marked - editing</span> @endif
                </span>
                <span>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="document.querySelectorAll('input[value=present]').forEach(r => r.checked = true)">All present</button>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Admission No</th><th>Name</th><th>Present</th><th>Absent</th><th>Late</th></tr></thead>
                    <tbody>
                    @forelse ($students as $s)
                        @php $current = old('status.'.$s->id, $existing[$s->id] ?? 'present'); @endphp
                        <tr>
                            <td>{{ $s->admission_no }}</td>
                            <td>{{ $s->user->name }}</td>
                            @foreach (['present', 'absent', 'late'] as $st)
                                <td><input type="radio" class="form-check-input" name="status[{{ $s->id }}]" value="{{ $st }}" @checked($current === $st)></td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No students in this class.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($students->isNotEmpty())
            <button class="btn btn-primary mt-3"><i class="bi bi-check2-all me-1"></i>Save attendance</button>
        @endif
    </form>
@endif
@endsection
