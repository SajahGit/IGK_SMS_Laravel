@extends('Layouts.LayoutApp')
@section('title', 'Learning Management')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Learning management</h3>
</div>
@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Create a course</strong></div>
    <div class="card-body">
        @if ($offerings->isEmpty())
            <p class="text-muted mb-0">All your assigned class-subject offerings already have a course, or no teaching assignments are available.</p>
        @else
            <form method="POST" action="{{ route('teacher.lms.courses.store') }}" class="row g-3">
                @csrf
                <div class="col-md-5">
                    <label class="form-label" for="class_subject_id">Teaching assignment</label>
                    <select class="form-select" id="class_subject_id" name="class_subject_id" required>
                        @foreach ($offerings as $offering)
                            <option value="{{ $offering->id }}">{{ $offering->schoolClass->full_name }} — {{ $offering->subject->name }}{{ $offering->term ? ' · '.$offering->term->name : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="title">Course title</label>
                    <input class="form-control" id="title" name="title" maxlength="160" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Create course</button>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2" maxlength="2000"></textarea>
                </div>
            </form>
        @endif
    </div>
</div>

<div class="row g-3">
    @forelse ($courses as $course)
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h4 class="h5">{{ $course->title }}</h4>
                    <p class="text-muted mb-3">{{ $course->classSubject->schoolClass->full_name }} · {{ $course->classSubject->subject->name }}</p>
                    @if ($course->description)<p>{{ $course->description }}</p>@endif
                    <div class="small text-muted mb-3">{{ $course->lessons_count }} lesson(s), {{ $course->assignments->count() }} assignment(s), {{ $course->assignments->sum(fn ($assignment) => $assignment->submissions->count()) }} submission(s)</div>
                    <a class="btn btn-primary btn-sm" href="{{ route('teacher.lms.courses.show', $course) }}">Manage course</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card shadow-sm"><div class="card-body text-muted">No learning courses yet. Create one from an assigned class-subject above.</div></div></div>
    @endforelse
</div>
@endsection
