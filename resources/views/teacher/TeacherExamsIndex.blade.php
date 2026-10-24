@extends('layouts.LayoutApp')
@section('title', 'Exams')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">My exams</h3>
    <a href="{{ route('teacher.exams.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create exam</a>
</div>
<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Exam</th><th>Class</th><th>Subject</th><th>Date</th><th>Max</th><th>Marks entered</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($exams as $e)
            <tr>
                <td>{{ $e->name }}</td>
                <td>{{ $e->class->full_name }}</td>
                <td>{{ $e->subject->name }}</td>
                <td>{{ $e->exam_date->format('d M Y') }}</td>
                <td>{{ $e->max_marks }}</td>
                <td>{{ $e->results_count }}</td>
                <td class="text-end">
                    <a href="{{ route('teacher.marks.edit', $e) }}" class="btn btn-sm btn-outline-primary">Enter marks</a>
                    <a href="{{ route('teacher.marks.show', $e) }}" class="btn btn-sm btn-outline-secondary">Results</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted">No exams yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $exams->links('pagination::bootstrap-5') }}</div>
@endsection
