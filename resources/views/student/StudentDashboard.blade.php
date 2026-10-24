@extends('layouts.LayoutApp')
@section('title', 'My Dashboard')
@section('content')
<h3 class="mb-1">Welcome, {{ $student->user->name }}</h3>
<p class="text-muted mb-4">{{ $student->class->full_name }} &middot; Admission no {{ $student->admission_no }}</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">@include('layouts.LayoutStat', ['icon' => 'calendar2-check-fill', 'value' => $summary['percentage'].'%', 'label' => 'Attendance', 'tone' => 'green'])</div>
    <div class="col-6 col-xl-3">@include('layouts.LayoutStat', ['icon' => 'check2-circle', 'value' => ($summary['present'] + $summary['late']).'/'.$summary['total'], 'label' => 'Days attended', 'tone' => 'cyan'])</div>
    <div class="col-6 col-xl-3">@include('layouts.LayoutStat', ['icon' => 'x-circle-fill', 'value' => $summary['absent'], 'label' => 'Days absent', 'tone' => 'rose'])</div>
    <div class="col-6 col-xl-3">@include('layouts.LayoutStat', ['icon' => 'trophy-fill', 'value' => $average !== null ? $average.'%' : '-', 'label' => 'Average result', 'tone' => 'violet'])</div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between"><span>Latest results</span><a href="{{ route('student.results') }}" class="small">All results</a></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Exam</th><th>Subject</th><th>Marks</th><th>Grade</th></tr></thead>
            <tbody>
            @forelse ($latestResults as $r)
                @php $pct = \App\Support\SchoolHelper::resultPercentage($r->marks, $r->exam->max_marks); $grade = \App\Support\SchoolHelper::gradeFor($pct); @endphp
                <tr>
                    <td>{{ $r->exam->name }}</td>
                    <td>{{ $r->exam->subject->name }}</td>
                    <td>{{ \App\Support\SchoolHelper::formatMarks($r->marks) }} / {{ $r->exam->max_marks }}</td>
                    <td><span class="badge bg-{{ \App\Support\SchoolHelper::gradeBadge($grade) }}">{{ $grade }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">No results yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
