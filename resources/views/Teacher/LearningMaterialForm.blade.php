@extends('Layouts.LayoutApp')

@section('title', $material ? 'Edit My Material' : 'Upload Learning Material')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1">{{ $material ? 'Edit My Learning Material' : 'Upload Learning Material' }}</h3>
        <p class="text-secondary mb-0">Active materials automatically appear in the Common Learning Materials Library.</p>
    </div>
    <a href="{{ route('teacher.learning-materials.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="glass-card p-4">
    <form method="POST"
          action="{{ $material ? route('teacher.learning-materials.update', $material) : route('teacher.learning-materials.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($material)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Material Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $material?->title) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Material Type <span class="text-danger">*</span></label>
                @if ($material)
                    <input type="text" class="form-control" value="{{ strtoupper($material->material_type) }}" disabled>
                @else
                    <select name="material_type" class="form-select" required>
                        @if ($canUploadVideo)
                            <option value="video" @selected(old('material_type') === 'video')>Video</option>
                        @endif
                        @if ($canUploadPdf)
                            <option value="pdf" @selected(old('material_type') === 'pdf')>PDF Document</option>
                        @endif
                    </select>
                @endif
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $material?->description) }}</textarea>
            </div>

            @if (! $material)
                <div class="col-md-6">
                    <label class="form-label">File <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" required>
                </div>
            @endif

            <div class="col-md-6">
                <label class="form-label">Thumbnail (optional)</label>
                <input type="file" name="thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>

            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">Uncategorized</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id', $material?->category_id) === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Course / Subject Metadata</label>
                <input type="text" name="course_subject_label" class="form-control" value="{{ old('course_subject_label', $material?->course_subject_label) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" @selected(old('status', $material?->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $material?->status) === 'inactive')>Inactive</option>
                </select>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ $material ? 'Save Changes' : 'Upload Material' }}</button>
            <a href="{{ route('teacher.learning-materials.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
