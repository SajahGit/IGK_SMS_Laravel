@extends('layouts.LayoutApp')
@section('title', 'Teacher Dashboard')
@section('content')
<div class="mb-4"><h3 class="mb-1">Teacher Dashboard</h3><p class="text-muted mb-0">Your classes, students and exams.</p></div>

<div class="row g-3 mb-4">
    <div class="col-md-4">@include('layouts.LayoutStat', ['icon' => 'easel2-fill', 'value' => $classCount, 'label' => 'My classes', 'tone' => 'indigo'])</div>
    <div class="col-md-4">@include('layouts.LayoutStat', ['icon' => 'people-fill', 'value' => $studentCount, 'label' => 'My students', 'tone' => 'cyan'])</div>
    <div class="col-md-4">@include('layouts.LayoutStat', ['icon' => 'journal-check', 'value' => $upcomingExams->count(), 'label' => 'Upcoming exams', 'tone' => 'amber'])</div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">My classes and subjects</div>
            <ul class="list-group list-group-flush">
                @forelse ($assignments as $a)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $a->schoolClass->full_name }} - {{ $a->subject->name }}</span>
                        <a href="{{ route('teacher.classes.students', $a->schoolClass) }}" class="small">Students</a>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No classes assigned yet. Ask the admin.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between">
                <span>Upcoming exams</span>
                <a href="{{ route('teacher.exams.create') }}" class="small">Create exam</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($upcomingExams as $e)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $e->name }} <span class="text-muted">({{ $e->class->full_name }})</span></span>
                        <span class="text-muted">{{ $e->exam_date->format('d M Y') }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No upcoming exams.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
<div class="mt-4">
    <a href="{{ route('teacher.attendance.create') }}" class="btn btn-primary"><i class="bi bi-clipboard2-check me-1"></i>Mark attendance</a>
    <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-primary"><i class="bi bi-journal-check me-1"></i>Exams and marks</a>
</div>
@endsection
