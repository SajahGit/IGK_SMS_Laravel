@extends('Layouts.LayoutApp')
@section('title', 'Learning Management')
@section('content')
<h3 class="mb-3">My learning courses</h3>
<div class="row g-3">
    @forelse ($courses as $course)
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h4 class="h5">{{ $course->title }}</h4>
                    <p class="text-muted">{{ $course->classSubject->schoolClass->full_name }} · {{ $course->classSubject->subject->name }}</p>
                    @if ($course->description)<p>{{ $course->description }}</p>@endif
                    <a class="btn btn-primary btn-sm" href="{{ route('student.lms.courses.show', $course) }}">Open course</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card shadow-sm"><div class="card-body text-muted">No learning courses are available for your active subject enrollments.</div></div></div>
    @endforelse
</div>
@endsection
