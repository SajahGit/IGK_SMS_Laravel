@extends('Layouts.LayoutApp')
@section('title', 'Edit Announcement')
@section('content')
<h3 class="mb-3">Edit announcement</h3>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input id="title" name="title" class="form-control" maxlength="150" required value="{{ old('title', $announcement->title) }}">
            </div>
            <div class="mb-3">
                <label for="body" class="form-label">Announcement</label>
                <textarea id="body" name="body" rows="5" class="form-control" required>{{ old('body', $announcement->body) }}</textarea>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="target_role" class="form-label">Audience</label>
                    <select id="target_role" name="target_role" class="form-select" required>
                        @foreach (['all' => 'All school', 'admin' => 'Administrators', 'registrar' => 'Registrars', 'teacher' => 'Teachers', 'accountant' => 'Accountants', 'student' => 'Students', 'parent' => 'Guardians'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('target_role', $announcement->target_role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="program_id" class="form-label">Program</label>
                    <select id="program_id" name="program_id" class="form-select">
                        <option value="">All programs</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected(old('program_id', $announcement->program_id) == $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="class_id" class="form-label">Section/class</label>
                    <select id="class_id" name="class_id" class="form-select">
                        <option value="">All sections</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected(old('class_id', $announcement->class_id) == $class->id)>{{ $class->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="published_at" class="form-label">Publish at</label>
                    <input id="published_at" type="datetime-local" name="published_at" class="form-control" required value="{{ old('published_at', $announcement->published_at->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-md-6">
                    <label for="expires_at" class="form-label">Expires at</label>
                    <input id="expires_at" type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at', $announcement->expires_at?->format('Y-m-d\TH:i')) }}">
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary">Save announcement</button>
                <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
