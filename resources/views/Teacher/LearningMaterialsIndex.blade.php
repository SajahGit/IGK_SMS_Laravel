@extends('Layouts.LayoutApp')

@section('title', 'My Learning Materials')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-collection-play-fill me-2 text-primary"></i>My Learning Materials</h3>
        <p class="text-secondary mb-0">Upload and manage your own videos and PDFs for the Common Learning Materials Library.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('learning-materials.library') }}" class="btn btn-outline-secondary">
            <i class="bi bi-collection me-1"></i>Browse Common Library
        </a>
        @if ($canUploadVideo || $canUploadPdf)
            <a href="{{ route('teacher.learning-materials.create') }}" class="btn btn-primary">
                <i class="bi bi-cloud-upload me-1"></i>Upload Material
            </a>
        @endif
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if (session('generated_share_url'))
    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <strong><i class="bi bi-link-45deg me-1"></i>Secure Share Link:</strong>
            <code class="ms-2 user-select-all" id="teacherShareUrl">{{ session('generated_share_url') }}</code>
        </div>
        <button type="button" class="btn btn-sm btn-light" onclick="navigator.clipboard.writeText(document.getElementById('teacherShareUrl').textContent.trim())">
            <i class="bi bi-clipboard me-1"></i>Copy Link
        </button>
    </div>
@endif

@if (! $canUploadVideo && ! $canUploadPdf)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>Your upload permission is currently disabled by the Administrator. You can still view and share your existing materials.
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="glass-card p-3">
            <div class="text-secondary small">My Uploads</div>
            <div class="fs-3 fw-bold">{{ $stats['total_uploads'] }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-3">
            <div class="text-secondary small">Active Materials</div>
            <div class="fs-3 fw-bold text-success">{{ $stats['active_uploads'] }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-3">
            <div class="text-secondary small">Total Views</div>
            <div class="fs-3 fw-bold text-info">{{ $stats['total_views'] }}</div>
        </div>
    </div>
</div>

<div class="glass-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type / Category</th>
                    <th>Status</th>
                    <th>Download (Admin Controlled)</th>
                    <th>Share</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($materials as $material)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $material->title }}</div>
                            <div class="small text-secondary">{{ $material->course_subject_label ?: 'General' }} &bull; {{ $material->access_logs_count ?? 0 }} accesses</div>
                        </td>
                        <td>
                            <span class="badge {{ $material->isVideo() ? 'bg-info text-dark' : 'bg-danger' }}">{{ strtoupper($material->material_type) }}</span>
                            <div class="small text-secondary mt-1">{{ $material->category?->name ?? 'Uncategorized' }}</div>
                        </td>
                        <td>
                            @if ($material->isExpired())
                                <span class="badge bg-warning text-dark">Expired</span>
                            @elseif ($material->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $material->allow_download ? 'bg-success' : 'bg-secondary' }}">
                                {{ $material->allow_download ? 'Allowed' : 'Disabled' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('teacher.learning-materials.share-links.store', $material) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-info">Generate Share Link</button>
                            </form>
                            @foreach ($material->shareLinks->whereNull('revoked_at')->take(2) as $link)
                                <form method="POST" action="{{ route('teacher.learning-materials.share-links.revoke', $link) }}" class="mt-1">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0">Revoke link ({{ $link->use_count }} uses)</button>
                                </form>
                            @endforeach
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('learning-materials.view', $material) }}" class="btn btn-outline-secondary"><i class="bi bi-play-circle"></i></a>
                                <a href="{{ route('teacher.learning-materials.edit', $material) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('teacher.learning-materials.destroy', $material) }}" onsubmit="return confirm('Delete your material?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">You have not uploaded any learning materials yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $materials->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
