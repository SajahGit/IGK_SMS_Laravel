@extends('Layouts.LayoutApp')
@section('title', 'Assign Subject to Program')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Assign Subject to Program</h3>
    <a href="{{ route('admin.curricula.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.curricula.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Program <span class="text-danger">*</span></label>
                    <select name="program_id" class="form-select" required>
                        <option value="">Select program</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">Select subject</option>
                        @foreach ($subjects as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Term Number <span class="text-danger">*</span></label>
                    <input type="number" name="term_number" class="form-control" required value="{{ old('term_number', 1) }}" min="1" max="12">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_elective" value="1" {{ old('is_elective') ? 'checked' : '' }}>
                        <label class="form-check-label">Elective</label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Assign</button>
                <a href="{{ route('admin.curricula.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
