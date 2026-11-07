@extends('Layouts.LayoutApp')
@section('title', 'Mark Attendance')
@section('content')
<h3 class="mb-3">Mark attendance</h3>
@include('Layouts.LayoutErrors')

<form method="GET" action="{{ route($attendanceRoutePrefix.'.attendance.create') }}" class="row g-2 mb-4">
    <div class="col-md-4">
        <select name="class_id" class="form-select" required>
            <option value="">Select class</option>
            @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="class_subject_id" class="form-select">
            @unless (auth()->user()->isTeacher())
                <option value="">General class session</option>
            @endunless
            @foreach ($classSubjects as $classSubject)
                <option value="{{ $classSubject->id }}" @selected($classSubjectId == $classSubject->id)>
                    {{ $classSubject->subject->name }}{{ $classSubject->term ? ' — '.$classSubject->term->name : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}" class="form-control" required></div>
    <div class="col-md-2"><input type="time" name="start_time" value="{{ $startTime }}" class="form-control" aria-label="Session start time" required></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Load students</button></div>
</form>

@if ($classId)
    <form method="POST" action="{{ route($attendanceRoutePrefix.'.attendance.store') }}">
        @csrf
        <input type="hidden" name="class_id" value="{{ $classId }}">
        <input type="hidden" name="date" value="{{ $date }}">
        @if ($session)
            <input type="hidden" name="start_time" value="{{ $session->start_time ? substr($session->start_time, 0, 5) : '' }}">
        @else
            <input type="hidden" name="start_time" value="{{ $startTime }}">
        @endif
        <input type="hidden" name="class_subject_id" value="{{ $classSubjectId }}">
        @if ($session)
            <input type="hidden" name="attendance_session_id" value="{{ $session->id }}">
        @endif

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <span>
                    <strong>{{ $session?->session_label ?? ($classSubjects->firstWhere('id', $classSubjectId)?->subject?->name ?? 'General class session') }}</strong>
                    · {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
                    @if ($session?->start_time) · {{ substr($session->start_time, 0, 5) }} @endif
                    @if ($existing->isNotEmpty()) <span class="badge bg-info text-dark ms-2">Already marked - editing</span> @endif
                </span>
                <span>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="document.querySelectorAll('input[value=present]').forEach(r => r.checked = true)">All present</button>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Admission No</th><th>Name</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th><th>Remarks</th></tr></thead>
                    <tbody>
                    @forelse ($students as $s)
                        @php $current = old('status.'.$s->id, $existing[$s->id]->status ?? 'present'); @endphp
                        <tr>
                            <td>{{ $s->admission_no }}</td>
                            <td>{{ $s->user->name }}</td>
                            @foreach (['present', 'absent', 'late', 'excused'] as $st)
                                <td><input type="radio" class="form-check-input" name="status[{{ $s->id }}]" value="{{ $st }}" @checked($current === $st)></td>
                            @endforeach
                            <td><input type="text" class="form-control form-control-sm" name="remarks[{{ $s->id }}]" maxlength="1000" value="{{ old('remarks.'.$s->id, $existing[$s->id]->remarks ?? '') }}" aria-label="Remarks for {{ $s->user->name }}"></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No active students in this class.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($students->isNotEmpty() && (auth()->user()->isAdmin() || auth()->user()->isTeacher() || auth()->user()->can('attendance.create') || $session))
            <button class="btn btn-primary mt-3"><i class="bi bi-check2-all me-1"></i>{{ $session ? 'Save attendance' : 'Create session and save attendance' }}</button>
        @elseif (! $session)
            <div class="alert alert-info mt-3">Only a Teacher/Admin can create a new attendance session. Select an existing session from Attendance History to correct it.</div>
        @endif
    </form>
@endif
@endsection
