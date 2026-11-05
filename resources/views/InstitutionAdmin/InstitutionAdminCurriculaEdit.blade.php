@extends('Layouts.LayoutApp')
@section('title', 'Edit Curriculum Assignment')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Edit Curriculum Assignment</h3>
    <a href="{{ route('admin.curricula.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.curricula.update', $curriculum) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Program <span class="text-danger">*</span></label>
                    <select name="program_id" class="form-select" required>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}" {{ old('program_id', $curriculum->program_id) === $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <select name="subject_id" class="form-select" required>
                        @foreach ($subjects as $s)
                            <option value="{{ $s->id }}" {{ old('subject_id', $curriculum->subject_id) === $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Term Number <span class="text-danger">*</span></label>
                    <input type="number" name="term_number" class="form-control" required value="{{ old('term_number', $curriculum->term_number) }}" min="1" max="12">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_elective" value="1" {{ old('is_elective', $curriculum->is_elective) ? 'checked' : '' }}>
                        <label class="form-check-label">Elective</label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update</button>
                <a href="{{ route('admin.curricula.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
