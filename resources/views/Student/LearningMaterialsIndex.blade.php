@extends('Layouts.LayoutApp')

@section('title', 'My Learning Materials')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-collection-play-fill me-2 text-primary"></i>Common Learning Materials Library</h3>
        <p class="text-secondary mb-0">Browse videos and PDF learning materials shared across the LMS.</p>
    </div>
</div>

{{-- Search & Filters --}}
<div class="glass-card p-3 mb-4">
    <form method="GET" action="{{ route('learning-materials.library') }}" id="studentLibraryFilterForm" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small text-secondary">Search Materials</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control live-student-input" placeholder="Type to search by title, subject, or keyword...">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-secondary">Type</label>
            <select name="type" class="form-select live-student-select">
                <option value="">All (Videos &amp; PDFs)</option>
                <option value="video" @selected($typeFilter === 'video')>Videos</option>
                <option value="pdf" @selected($typeFilter === 'pdf')>PDF Documents</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-secondary">Category</label>
            <select name="category_id" class="form-select live-student-select">
                <option value="">All Categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) $categoryFilter === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <a href="{{ route('learning-materials.library') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<div id="studentLibraryWrapper">

{{-- Continue Watching --}}
@if ($continueWatching->isNotEmpty())
    <h5 class="mb-3"><i class="bi bi-play-btn me-2 text-info"></i>Continue Watching</h5>
    <div class="row g-3 mb-4">
        @foreach ($continueWatching as $item)
            @if ($item->material)
                <div class="col-md-6 col-xl-3">
                    <div class="glass-card p-3 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="badge bg-info text-dark mb-2">Resume at {{ gmdate('i:s', $item->last_position) }}</span>
                            <h6 class="mb-1">{{ $item->material->title }}</h6>
                            <div class="small text-secondary">{{ $item->material->category?->name ?? 'General' }}</div>
                        </div>
                        <a href="{{ route('learning-materials.view', $item->material) }}" class="btn btn-sm btn-outline-info mt-3">
                            <i class="bi bi-play-fill me-1"></i>Resume Video
                        </a>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif

{{-- Recently Viewed --}}
@if ($recentlyViewed->isNotEmpty())
    <h5 class="mb-3"><i class="bi bi-clock-history me-2 text-secondary"></i>Recently Viewed</h5>
    <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach ($recentlyViewed as $log)
            @if ($log->material)
                <a href="{{ route('learning-materials.view', $log->material) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi {{ $log->material->isVideo() ? 'bi-camera-video' : 'bi-file-earmark-pdf' }} me-1"></i>
                    {{ $log->material->title }}
                </a>
            @endif
        @endforeach
    </div>
@endif

{{-- All Materials --}}
<div class="row g-3">
    @forelse ($materials as $material)
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="glass-card h-100 d-flex flex-column overflow-hidden">
                @if ($material->isVideo() && $material->thumbnail_path)
                    <img src="{{ route('learning-materials.thumbnail', $material) }}" alt="{{ $material->title }}" class="w-100" style="height: 150px; object-fit: cover;">
                @else
                    <div class="d-flex align-items-center justify-content-center bg-body-tertiary" style="height: 150px;">
                        <i class="bi {{ $material->isVideo() ? 'bi-play-circle-fill text-info' : 'bi-file-earmark-pdf-fill text-danger' }}" style="font-size: 3rem;"></i>
                    </div>
                @endif
                <div class="p-3 d-flex flex-column flex-grow-1 justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge {{ $material->isVideo() ? 'bg-info text-dark' : 'bg-danger' }}">
                                {{ strtoupper($material->material_type) }}
                            </span>
                            <span class="small text-secondary">{{ $material->category?->name ?? 'General' }}</span>
                        </div>
                        <h6 class="mb-1">{{ $material->title }}</h6>
                        @if ($material->course_subject_label)
                            <div class="small text-primary mb-1">{{ $material->course_subject_label }}</div>
                        @endif
                        <p class="small text-secondary mb-2">{{ \Illuminate\Support\Str::limit($material->description, 80) }}</p>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <a href="{{ route('learning-materials.view', $material) }}" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="bi {{ $material->isVideo() ? 'bi-play-fill' : 'bi-book-open' }} me-1"></i>
                            {{ $material->isVideo() ? 'Watch' : 'Read PDF' }}
                        </a>
                        @if ($material->allow_download)
                            <a href="{{ route('learning-materials.download', $material) }}" class="btn btn-sm btn-outline-success" title="Download">
                                <i class="bi bi-download"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="glass-card p-5 text-center text-secondary">
                No active learning materials match your filter.
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $materials->links('pagination::bootstrap-5') }}
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('studentLibraryFilterForm');
    const wrapper = document.getElementById('studentLibraryWrapper');
    if (!form || !wrapper) return;

    let timer;
    const runSearch = () => {
        const url = new URL(form.action, window.location.origin);
        const params = new URLSearchParams();
        new FormData(form).forEach((val, key) => {
            if (String(val).trim() !== '') params.set(key, val);
        });
        url.search = params.toString();

        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const next = doc.getElementById('studentLibraryWrapper');
                if (next) {
                    wrapper.innerHTML = next.innerHTML;
                    window.history.replaceState({}, '', url.toString());
                }
            })
            .catch(() => {});
    };

    form.addEventListener('submit', (e) => { e.preventDefault(); runSearch(); });
    form.querySelectorAll('.live-student-input').forEach(el => {
        el.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(runSearch, 250); });
    });
    form.querySelectorAll('.live-student-select').forEach(el => el.addEventListener('change', runSearch));
});
</script>
@endsection
