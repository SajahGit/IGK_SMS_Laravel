@extends('Layouts.LayoutApp')
@section('title', $course->title)
@section('content')
<div class="mb-3">
    <a href="{{ route('student.lms.index') }}" class="small">My learning courses</a>
    <h3 class="mb-0">{{ $course->title }}</h3>
    <div class="text-muted">{{ $course->classSubject->schoolClass->full_name }} · {{ $course->classSubject->subject->name }}</div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Lessons</strong></div>
    <div class="card-body">
        @forelse ($course->lessons as $lesson)
            <article class="border-bottom pb-3 mb-3">
                <h4 class="h5">{{ $lesson->title }}</h4>
                <div style="white-space: pre-wrap">{{ $lesson->content }}</div>
            </article>
        @empty
            <p class="text-muted mb-0">No published lessons are available yet.</p>
        @endforelse
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Modules and past papers</strong></div>
    <div class="card-body">
        @forelse ($course->materials as $material)
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border rounded p-3 mb-2">
                <div>
                    <span class="badge {{ $material->type === 'past_paper' ? 'bg-info' : 'bg-primary' }}">{{ $material->type === 'past_paper' ? 'Past paper' : 'Module' }}</span>
                    <strong class="ms-2">{{ $material->title }}</strong>
                    @if ($material->description)<div class="small text-muted mt-1">{{ $material->description }}</div>@endif
                </div>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('student.lms.materials.download', [$course, $material]) }}">Download PDF</a>
            </div>
        @empty
            <p class="text-muted mb-0">No published PDF modules or past papers are available yet.</p>
        @endforelse
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header"><strong>Assignments</strong></div>
    <div class="card-body">
        @forelse ($course->assignments as $assignment)
            @php($submission = $assignment->submissions->first())
            <article class="border-bottom pb-4 mb-4">
                <h4 class="h5">{{ $assignment->title }}</h4>
                <div class="mb-2" style="white-space: pre-wrap">{{ $assignment->instructions }}</div>
                <div class="small text-muted mb-3">Due: {{ $assignment->due_at?->format('d M Y H:i') ?? 'No deadline' }} · Maximum: {{ $assignment->max_points }} points</div>

                @if ($submission?->graded_at)
                    <div class="alert alert-success">
                        <strong>Grade: {{ $submission->score }} / {{ $assignment->max_points }}</strong>
                        @if ($submission->feedback)<div class="mt-1">{{ $submission->feedback }}</div>@endif
                    </div>
                    <div class="border rounded p-3 mb-2" style="white-space: pre-wrap">{{ $submission->body }}</div>
                    <div class="small text-muted">Submitted {{ $submission->submitted_at->format('d M Y H:i') }}</div>
                @elseif ($assignment->due_at?->isPast())
                    <p class="text-muted mb-0">The deadline has passed. {{ $submission ? 'Your submitted work is shown below.' : 'No submission was recorded.' }}</p>
                    @if ($submission)<div class="border rounded p-3 mt-2" style="white-space: pre-wrap">{{ $submission->body }}</div>@endif
                @else
                    <form method="POST" action="{{ route('student.lms.submissions.store', [$course, $assignment]) }}">
                        @csrf
                        <label class="form-label" for="submission_{{ $assignment->id }}">Your work</label>
                        <textarea class="form-control mb-2" id="submission_{{ $assignment->id }}" name="body" rows="6" maxlength="50000" required>{{ old('body', $submission?->body) }}</textarea>
                        @error('body') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                        @error('submission') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                        <button class="btn btn-primary">{{ $submission ? 'Update submission' : 'Submit assignment' }}</button>
                        @if ($submission)<span class="small text-muted ms-2">Last submitted {{ $submission->submitted_at->format('d M Y H:i') }}</span>@endif
                    </form>
                @endif
            </article>
        @empty
            <p class="text-muted mb-0">No published assignments are available yet.</p>
        @endforelse
    </div>
</div>
@endsection
