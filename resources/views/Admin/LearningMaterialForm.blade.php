@extends('Layouts.LayoutApp')

@section('title', $material ? 'Edit Learning Material' : 'Upload Learning Material')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1">{{ $material ? 'Edit Learning Material' : 'Upload Learning Material' }}</h3>
        <p class="text-secondary mb-0">Files are stored privately and delivered only through authenticated LMS endpoints.</p>
    </div>
    <a href="{{ route('admin.learning-materials.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Management
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
          action="{{ $material ? route('admin.learning-materials.update', $material) : route('admin.learning-materials.store') }}"
          enctype="multipart/form-data"
          id="adminMaterialForm">
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
                    <select name="material_type" class="form-select" id="materialTypeSelect" required>
                        <option value="video" @selected(old('material_type', 'video') === 'video')>Video (MP4, WebM, MOV)</option>
                        <option value="pdf" @selected(old('material_type') === 'pdf')>PDF Document</option>
                    </select>
                @endif
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $material?->description) }}</textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">{{ $material ? 'Replace File (optional)' : 'Upload File' }} @if(!$material)<span class="text-danger">*</span>@endif</label>
                <input type="file" name="file" class="form-control" @if(!$material) required @endif>
                <div class="form-text text-secondary">Videos up to 64 MB (server limit), PDFs up to 32 MB.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Thumbnail (optional, for Videos)</label>
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
                <label class="form-label">Course / Subject Metadata (optional)</label>
                <input type="text" name="course_subject_label" class="form-control" value="{{ old('course_subject_label', $material?->course_subject_label) }}" placeholder="e.g. Grade 10 Physics">
            </div>

            <div class="col-md-4">
                <label class="form-label">Visibility <span class="text-danger">*</span></label>
                <select name="visibility" class="form-select" required>
                    <option value="library" @selected(old('visibility', $material?->visibility ?? 'library') === 'library')>Common Learning Library</option>
                    <option value="private" @selected(old('visibility', $material?->visibility) === 'private')>Private / Share Link Only</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" @selected(old('status', $material?->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $material?->status) === 'inactive')>Inactive</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="allow_download" value="1" id="allowDownloadSwitch" @checked(old('allow_download', $material?->allow_download ?? true))>
                    <label class="form-check-label" for="allowDownloadSwitch">Allow Download (ON / OFF)</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" name="expiry_enabled" value="1" id="expiryEnabledSwitch" @checked(old('expiry_enabled', $material?->expiry_enabled ?? false))>
                    <label class="form-check-label" for="expiryEnabledSwitch">Enable Expiry Date</label>
                </div>
                <input type="datetime-local" name="expires_at" id="expiresAtInput" class="form-control"
                       value="{{ old('expires_at', $material?->expires_at?->format('Y-m-d\TH:i')) }}">
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle me-1"></i>{{ $material ? 'Save Changes' : 'Upload Material' }}
            </button>
            <a href="{{ route('admin.learning-materials.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('expiryEnabledSwitch');
    const input = document.getElementById('expiresAtInput');
    const syncExpiry = () => {
        const enabled = toggle.checked;
        input.disabled = !enabled;
        input.required = enabled;
        if (!enabled) input.value = '';
    };
    toggle.addEventListener('change', syncExpiry);
    syncExpiry();
});
</script>
@endsection
