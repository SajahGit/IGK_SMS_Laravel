@extends('Layouts.LayoutApp')
@section('title', $exam ? 'Edit Exam' : 'Create Exam')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">{{ $exam ? 'Edit exam' : 'Create exam' }}</h3>
        <small class="text-muted">Select a subject offered for the chosen section and term.</small>
    </div>
    <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary">Back to exams</a>
</div>

@include('Layouts.LayoutErrors')

<form method="POST" action="{{ $exam ? route('admin.exams.update', $exam) : route('admin.exams.store') }}" class="card shadow-sm border-0">
    @csrf
    @if ($exam) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Exam name</label>
            <input id="name" name="name" class="form-control" value="{{ old('name', $exam?->name) }}" maxlength="100" required>
        </div>
        <div class="col-md-3">
            <label for="type" class="form-label">Type</label>
            <input id="type" name="type" class="form-control" value="{{ old('type', $exam?->type ?? 'final') }}" maxlength="30" required>
        </div>
        <div class="col-md-3">
            <label for="exam_date" class="form-label">Exam date</label>
            <input type="date" id="exam_date" name="exam_date" class="form-control" value="{{ old('exam_date', $exam?->exam_date?->toDateString()) }}" required>
        </div>
        <div class="col-md-4">
            <label for="class_id" class="form-label">Class/Section</label>
            <select id="class_id" name="class_id" class="form-select" required>
                <option value="">Select section</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(old('class_id', $exam?->class_id) == $class->id)>{{ $class->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="subject_id" class="form-label">Subject</label>
            <select id="subject_id" name="subject_id" class="form-select" required>
                <option value="">Select subject</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected(old('subject_id', $exam?->subject_id) == $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="term_id" class="form-label">Term</label>
            <select id="term_id" name="term_id" class="form-select" required>
                <option value="">Select term</option>
                @foreach ($terms as $term)
                    <option value="{{ $term->id }}" @selected(old('term_id', $exam?->term_id) == $term->id)>{{ $term->name }} — {{ $term->academicYear?->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="max_marks" class="form-label">Maximum marks</label>
            <input type="number" id="max_marks" name="max_marks" min="1" max="1000" class="form-control" value="{{ old('max_marks', $exam?->max_marks ?? 100) }}" required>
        </div>
        <div class="col-md-3">
            <label for="weight" class="form-label">Weight (%)</label>
            <input type="number" id="weight" name="weight" min="1" max="100" class="form-control" value="{{ old('weight', $exam?->weight ?? 100) }}" required>
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
        <a href="{{ route('admin.exams.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary">{{ $exam ? 'Save changes' : 'Create exam' }}</button>
    </div>
</form>
@endsection
