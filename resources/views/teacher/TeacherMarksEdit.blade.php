@extends('layouts.LayoutApp')
@section('title', 'Enter Marks')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Enter marks</h3>
    <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
<p class="text-muted">{{ $exam->name }} &middot; {{ $exam->class->full_name }} &middot; {{ $exam->subject->name }} &middot; Maximum marks: <strong>{{ $exam->max_marks }}</strong></p>
@include('layouts.LayoutErrors')

<form method="POST" action="{{ route('teacher.marks.update', $exam) }}">
    @csrf
    <div class="card shadow-sm"><div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Admission No</th><th>Name</th><th style="width:180px">Marks (out of {{ $exam->max_marks }})</th></tr></thead>
            <tbody>
            @forelse ($students as $s)
                <tr>
                    <td>{{ $s->admission_no }}</td>
                    <td>{{ $s->user->name }}</td>
                    <td>
                        <input type="number" step="0.01" min="0" max="{{ $exam->max_marks }}" name="marks[{{ $s->id }}]"
                               value="{{ old('marks.'.$s->id, isset($existing[$s->id]) ? \App\Support\SchoolHelper::formatMarks($existing[$s->id]) : '') }}"
                               class="form-control">
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">No students in this class.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    @if ($students->isNotEmpty())
        <button class="btn btn-primary mt-3"><i class="bi bi-check2-all me-1"></i>Save marks</button>
    @endif
</form>
@endsection
