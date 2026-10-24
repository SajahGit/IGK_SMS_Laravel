@extends('layouts.LayoutApp')
@section('title', 'Create Exam')
@section('content')
<h3 class="mb-3">Create exam</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('teacher.exams.store') }}">
        @csrf
        @include('layouts.LayoutErrors')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Exam name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Term 1 Test" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Class and subject</label>
                <select name="assignment" class="form-select" required>
                    <option value="">Select</option>
                    @foreach ($assignments as $a)
                        <option value="{{ $a->class_id }}-{{ $a->subject_id }}" @selected(old('assignment') == $a->class_id.'-'.$a->subject_id)>
                            {{ $a->schoolClass->full_name }} - {{ $a->subject->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Exam date</label>
                <input type="date" name="exam_date" class="form-control" value="{{ old('exam_date') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Maximum marks</label>
                <input type="number" name="max_marks" class="form-control" value="{{ old('max_marks', 100) }}" min="1" max="1000" required>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Create exam</button>
            <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div></div>
@endsection
