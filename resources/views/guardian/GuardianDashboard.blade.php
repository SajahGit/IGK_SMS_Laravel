@extends('layouts.LayoutApp')
@section('title', 'My Children')
@section('content')
<div class="mb-4">
    <h3 class="mb-1">My children</h3>
    <p class="text-muted mb-0">Attendance and results of your children.</p>
</div>
<div class="row g-3">
    @forelse ($children as $c)
        @php
            $att = $c->attendance_percentage;
            $attTone = $att >= 85 ? 'good' : ($att >= 75 ? 'warn' : 'bad');
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card child-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar">{{ strtoupper(mb_substr($c->user->name, 0, 1)) }}</div>
                        <div>
                            <h5 class="mb-0">{{ $c->user->name }}</h5>
                            <div class="text-muted small">{{ $c->class->full_name }} &middot; {{ $c->admission_no }}</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Attendance</span><strong>{{ $att }}%</strong></div>
                    <div class="progress mb-3"><div class="progress-bar {{ $attTone }}" style="width: {{ min($att, 100) }}%"></div></div>

                    <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Average result</span><strong>{{ $c->average !== null ? $c->average.'%' : '-' }}</strong></div>
                    <div class="progress mb-4"><div class="progress-bar" style="width: {{ min($c->average ?? 0, 100) }}%"></div></div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('guardian.attendance', $c) }}" class="btn btn-sm btn-outline-primary flex-fill"><i class="bi bi-calendar2-check me-1"></i>Attendance</a>
                        <a href="{{ route('guardian.results', $c) }}" class="btn btn-sm btn-outline-success flex-fill"><i class="bi bi-trophy me-1"></i>Results</a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">No children are linked to your account yet. Please contact the school admin.</div></div>
    @endforelse
</div>
@endsection
