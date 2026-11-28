@extends('Layouts.LayoutApp')

@section('title', 'Video & PDF Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-collection-play-fill me-2 text-primary"></i>Video &amp; PDF Learning Materials</h3>
        <p class="text-secondary mb-0">Common Learning Materials Library — manage uploads, download permissions, expiry, share links, and access logs.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('learning-materials.library') }}" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i>View Library
        </a>
        <a href="{{ route('admin.learning-materials.create') }}" class="btn btn-primary">
            <i class="bi bi-cloud-upload me-1"></i>Upload Material
        </a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if (session('generated_share_url'))
    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <strong><i class="bi bi-link-45deg me-1"></i>Secure Share Link Ready:</strong>
            <code class="ms-2 user-select-all" id="generatedShareUrl">{{ session('generated_share_url') }}</code>
        </div>
        <button type="button" class="btn btn-sm btn-light" onclick="navigator.clipboard.writeText(document.getElementById('generatedShareUrl').textContent.trim())">
            <i class="bi bi-clipboard me-1"></i>Copy Link
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="glass-card p-3 h-100">
            <div class="text-secondary small">Total Materials</div>
            <div class="fs-3 fw-bold">{{ $stats['total_videos'] + $stats['total_pdfs'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="glass-card p-3 h-100">
            <div class="text-secondary small">Videos / PDFs</div>
            <div class="fs-4 fw-bold">{{ $stats['total_videos'] }} / {{ $stats['total_pdfs'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="glass-card p-3 h-100">
            <div class="text-secondary small">Active</div>
            <div class="fs-3 fw-bold text-success">{{ $stats['active_materials'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="glass-card p-3 h-100">
            <div class="text-secondary small">Expired</div>
            <div class="fs-3 fw-bold text-warning">{{ $stats['expired_materials'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="glass-card p-3 h-100">
            <div class="text-secondary small">Video Plays</div>
            <div class="fs-3 fw-bold text-info">{{ $stats['total_video_plays'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="glass-card p-3 h-100">
            <div class="text-secondary small">PDF Views</div>
            <div class="fs-3 fw-bold text-primary">{{ $stats['total_pdf_views'] }}</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="glass-card p-3 mb-4">
    <form method="GET" action="{{ route('admin.learning-materials.index') }}" id="materialsFilterForm" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-secondary">Search</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control live-filter-input" placeholder="Type to search title, subject...">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Type</label>
            <select name="type" class="form-select live-filter-select">
                <option value="">All Types</option>
                <option value="video" @selected($typeFilter === 'video')>Video</option>
                <option value="pdf" @selected($typeFilter === 'pdf')>PDF</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Category</label>
            <select name="category_id" class="form-select live-filter-select">
                <option value="">All Categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) $categoryFilter === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Uploader</label>
            <select name="teacher_id" class="form-select live-filter-select">
                <option value="">All Uploaders</option>
                @foreach ($uploaders as $uploader)
                    <option value="{{ $uploader->id }}" @selected((int) $teacherFilter === $uploader->id)>{{ $uploader->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Status</label>
            <select name="status" class="form-select live-filter-select">
                <option value="">All</option>
                <option value="active" @selected($statusFilter === 'active')>Active</option>
                <option value="inactive" @selected($statusFilter === 'inactive')>Inactive</option>
                <option value="expired" @selected($statusFilter === 'expired')>Expired</option>
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <a href="{{ route('admin.learning-materials.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

{{-- Materials Table --}}
<div id="materialsTableWrapper">
<div class="glass-card p-3 mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Type / Category</th>
                    <th>Uploader</th>
                    <th>Status</th>
                    <th>Download</th>
                    <th>Expiry</th>
                    <th>Share Links</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($materials as $material)
                    @php
                        $expired = $material->isExpired();
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $material->title }}</div>
                            <div class="small text-secondary">
                                {{ $material->course_subject_label ?: 'General' }} &bull; {{ $material->formatted_size }}
                                &bull; {{ $material->access_logs_count ?? 0 }} accesses
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $material->isVideo() ? 'bg-info text-dark' : 'bg-danger' }}">
                                {{ strtoupper($material->material_type) }}
                            </span>
                            <div class="small text-secondary mt-1">{{ $material->category?->name ?? 'Uncategorized' }}</div>
                        </td>
                        <td>
                            <div>{{ $material->uploader?->name ?? 'Unknown' }}</div>
                            <div class="small text-secondary">{{ ucfirst($material->uploader?->role ?? '') }}</div>
                        </td>
                        <td>
                            @if ($expired)
                                <span class="badge bg-warning text-dark">Expired</span>
                            @elseif ($material->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.learning-materials.toggle-download', $material) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $material->allow_download ? 'btn-outline-success' : 'btn-outline-secondary' }}">
                                    {{ $material->allow_download ? 'ON' : 'OFF' }}
                                </button>
                            </form>
                        </td>
                        <td class="small">
                            @if ($material->expiry_enabled && $material->expires_at)
                                {{ $material->expires_at->format('Y-m-d H:i') }}
                            @else
                                <span class="text-secondary">No expiry</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.learning-materials.share-links.store', $material) }}" class="mb-1">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-link-45deg me-1"></i>Generate Share Link
                                </button>
                            </form>
                            @foreach ($material->shareLinks->whereNull('revoked_at')->take(2) as $link)
                                <div class="d-flex align-items-center gap-1 small mt-1">
                                    <span class="text-secondary">Uses: {{ $link->use_count }}</span>
                                    <form method="POST" action="{{ route('admin.learning-materials.share-links.revoke', $link) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm text-danger p-0">Revoke</button>
                                    </form>
                                </div>
                            @endforeach
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('learning-materials.view', $material) }}" class="btn btn-outline-secondary" title="Open">
                                    <i class="bi bi-play-circle"></i>
                                </a>
                                <a href="{{ route('admin.learning-materials.edit', $material) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.learning-materials.toggle-status', $material) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-warning" title="Toggle Active/Inactive">
                                        <i class="bi bi-power"></i>
                                    </button>
                                </form>
                                @if (! $expired)
                                    <form method="POST" action="{{ route('admin.learning-materials.expire-now', $material) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary" title="Expire Now">
                                            <i class="bi bi-hourglass-bottom"></i>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.learning-materials.destroy', $material) }}" onsubmit="return confirm('Delete this learning material permanently?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-4">No learning materials found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $materials->links('pagination::bootstrap-5') }}
    </div>
</div>
</div>

{{-- Global Settings, Categories & User Permissions --}}
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="glass-card p-3 h-100">
            <h5 class="mb-3"><i class="bi bi-shield-lock me-2"></i>Global Module Permissions</h5>
            <form method="POST" action="{{ route('admin.learning-materials.settings.update') }}">
                @csrf
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="enabled" value="1" id="globalLib" @checked($settings['enabled'])>
                    <label class="form-check-label" for="globalLib">Enable Common Learning Materials Library</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="teacher_uploads" value="1" id="teacherUp" @checked($settings['teacher_uploads'])>
                    <label class="form-check-label" for="teacherUp">Allow Teachers to Upload Materials</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="teacher_video_uploads" value="1" id="teacherVid" @checked($settings['teacher_video_uploads'])>
                    <label class="form-check-label" for="teacherVid">Allow Teacher Video Uploads</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="teacher_pdf_uploads" value="1" id="teacherPdf" @checked($settings['teacher_pdf_uploads'])>
                    <label class="form-check-label" for="teacherPdf">Allow Teacher PDF Uploads</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="student_access" value="1" id="studentLib" @checked($settings['student_access'])>
                    <label class="form-check-label" for="studentLib">Allow Students to Access Library</label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Save Global Settings</button>
            </form>

            <hr class="border-secondary my-3">
            <h6 class="mb-2">Add Category</h6>
            <form method="POST" action="{{ route('admin.learning-materials.categories.store') }}" class="d-flex gap-2">
                @csrf
                <input type="text" name="name" class="form-control form-control-sm" placeholder="New category name" required>
                <button type="submit" class="btn btn-outline-primary btn-sm">Add</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="glass-card p-3 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Member Access &amp; Upload Permissions</h5>
                <form method="GET" action="{{ route('admin.learning-materials.index') }}" id="memberFilterForm" class="row g-2 align-items-center flex-grow-1 justify-content-end" style="max-width: 560px;">
                    <div class="col-12 col-sm-5">
                        <input type="text" name="user_search" value="{{ $userSearch ?? '' }}" class="form-control form-control-sm live-member-input" placeholder="Search member...">
                    </div>
                    <div class="col-6 col-sm-3">
                        <select name="user_role" class="form-select form-select-sm live-member-select">
                            <option value="">All Roles</option>
                            @foreach (($userRoles ?? collect()) as $roleOption)
                                <option value="{{ $roleOption }}" @selected(($userRole ?? '') === $roleOption)>{{ $roleOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-sm-4">
                        <select name="user_class_id" class="form-select form-select-sm live-member-select">
                            <option value="">All Classes</option>
                            @foreach (($classes ?? collect()) as $cls)
                                <option value="{{ $cls->id }}" @selected((int) ($userClassId ?? 0) === $cls->id)>{{ $cls->name }}{{ $cls->section ? ' - '.$cls->section : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            {{-- Bulk Access Controls (Selected, Role-wise, Class-wise, or All) --}}
            <form method="POST" action="{{ route('admin.learning-materials.users.bulk-access') }}" id="bulkAccessForm" class="p-2 mb-3 rounded-3 border bg-body-tertiary">
                @csrf
                <div id="bulkSelectedInputs"></div>
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-sm-6 col-xl-4">
                        <select name="scope" id="bulkScopeSelect" class="form-select form-select-sm">
                            <option value="selected">Selected Checkboxes</option>
                            <option value="role">By Role / Category</option>
                            <option value="class">By Student Class</option>
                            <option value="all">All Members</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-4 d-none" id="bulkRoleCol">
                        <select name="target_role" class="form-select form-select-sm">
                            <option value="">Choose Role...</option>
                            @foreach (($userRoles ?? collect()) as $roleOption)
                                <option value="{{ $roleOption }}">{{ $roleOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-4 d-none" id="bulkClassCol">
                        <select name="class_id" class="form-select form-select-sm">
                            <option value="">Choose Class...</option>
                            @foreach (($classes ?? collect()) as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }}{{ $cls->section ? ' - '.$cls->section : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-4">
                        <select name="permission_field" class="form-select form-select-sm">
                            <option value="lms_video_access">Video Access (Videos Only)</option>
                            <option value="lms_pdf_access">PDF Access (PDFs Only)</option>
                            <option value="lms_material_access">All Materials (Video + PDF)</option>
                            <option value="lms_can_upload_materials">Upload Permission</option>
                        </select>
                    </div>
                    <div class="col-12 col-xl-auto d-flex gap-2">
                        <button type="submit" name="grant" value="1" class="btn btn-sm btn-success">
                            <i class="bi bi-check-all me-1"></i>Grant ON
                        </button>
                        <button type="submit" name="grant" value="0" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x-circle me-1"></i>Revoke OFF
                        </button>
                    </div>
                </div>
            </form>

            <div id="memberAccessTableWrapper">
            <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 32px;"><input type="checkbox" class="form-check-input" id="selectAllMembers" title="Select all visible"></th>
                            <th>Member</th>
                            <th>Role / Class</th>
                            <th>Video Access</th>
                            <th>PDF Access</th>
                            <th>Can Upload</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($managedUsers as $managed)
                            @php
                                $studentRow = ($studentClassMap ?? collect())->get($managed->id);
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input member-checkbox" value="{{ $managed->id }}">
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $managed->name }}</div>
                                    <div class="small text-secondary">{{ $managed->email }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $managed->role }}</span>
                                    @if ($studentRow?->class)
                                        <div class="small text-info mt-1">{{ $studentRow->class->name }}{{ $studentRow->class->section ? ' - '.$studentRow->class->section : '' }}</div>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.learning-materials.users.toggle-access', $managed) }}">
                                        @csrf
                                        <input type="hidden" name="permission_field" value="lms_video_access">
                                        <button type="submit" class="btn btn-sm {{ ($managed->lms_material_access && $managed->lms_video_access) ? 'btn-outline-success' : 'btn-outline-secondary' }}">
                                            {{ ($managed->lms_material_access && $managed->lms_video_access) ? 'ON' : 'OFF' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.learning-materials.users.toggle-access', $managed) }}">
                                        @csrf
                                        <input type="hidden" name="permission_field" value="lms_pdf_access">
                                        <button type="submit" class="btn btn-sm {{ ($managed->lms_material_access && $managed->lms_pdf_access) ? 'btn-outline-success' : 'btn-outline-secondary' }}">
                                            {{ ($managed->lms_material_access && $managed->lms_pdf_access) ? 'ON' : 'OFF' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.learning-materials.users.toggle-access', $managed) }}">
                                        @csrf
                                        <input type="hidden" name="permission_field" value="lms_can_upload_materials">
                                        <button type="submit" class="btn btn-sm {{ $managed->lms_can_upload_materials ? 'btn-outline-success' : 'btn-outline-secondary' }}">
                                            {{ $managed->lms_can_upload_materials ? 'ON' : 'OFF' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                {{ $managedUsers->links('pagination::bootstrap-5') }}
            </div>
            </div>
        </div>
    </div>
</div>

{{-- Usage Analytics & Access Logs --}}
<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-card p-3 h-100">
            <h5 class="mb-3"><i class="bi bi-bar-chart-fill me-2"></i>Most Viewed Materials</h5>
            @forelse ($mostViewed as $item)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-25">
                    <div>
                        <div class="fw-semibold">{{ $item->title }}</div>
                        <div class="small text-secondary">{{ strtoupper($item->material_type) }} &bull; {{ $item->uploader?->name }}</div>
                    </div>
                    <span class="badge bg-info text-dark">{{ $item->access_logs_count ?? 0 }} views</span>
                </div>
            @empty
                <p class="text-secondary mb-0">No views recorded yet.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-7">
        <div class="glass-card p-3 h-100">
            <h5 class="mb-3"><i class="bi bi-clock-history me-2"></i>Recent Access Logs</h5>
            <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Material</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLogs as $log)
                            <tr>
                                <td class="small text-secondary">{{ $log->accessed_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $log->user?->name ?? 'Guest' }}</td>
                                <td>{{ $log->material?->title ?? 'Deleted' }}</td>
                                <td><span class="badge bg-secondary">{{ $log->access_type }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary">No logs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const debounce = (fn, wait = 250) => {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
    };

    const bindLiveForm = (formId, wrapperId, inputSelector, selectSelector) => {
        const form = document.getElementById(formId);
        const wrapper = document.getElementById(wrapperId);
        if (!form || !wrapper) return;

        const runFetch = () => {
            const url = new URL(form.action, window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            const formData = new FormData(form);
            formData.forEach((val, key) => {
                if (String(val).trim() === '') {
                    currentParams.delete(key);
                } else {
                    currentParams.set(key, val);
                }
            });
            url.search = currentParams.toString();

            fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nextWrapper = doc.getElementById(wrapperId);
                    if (nextWrapper) {
                        wrapper.innerHTML = nextWrapper.innerHTML;
                        window.history.replaceState({}, '', url.toString());
                        bindMemberCheckboxes();
                    }
                })
                .catch(() => {});
        };

        form.addEventListener('submit', (e) => { e.preventDefault(); runFetch(); });
        form.querySelectorAll(inputSelector).forEach(el => el.addEventListener('input', debounce(runFetch, 250)));
        form.querySelectorAll(selectSelector).forEach(el => el.addEventListener('change', runFetch));
    };

    const bindMemberCheckboxes = () => {
        const selectAll = document.getElementById('selectAllMembers');
        const boxes = document.querySelectorAll('.member-checkbox');
        if (selectAll) {
            selectAll.onchange = () => boxes.forEach(cb => { cb.checked = selectAll.checked; });
        }
    };

    bindMemberCheckboxes();
    bindLiveForm('materialsFilterForm', 'materialsTableWrapper', '.live-filter-input', '.live-filter-select');
    bindLiveForm('memberFilterForm', 'memberAccessTableWrapper', '.live-member-input', '.live-member-select');

    const scopeSelect = document.getElementById('bulkScopeSelect');
    const roleCol = document.getElementById('bulkRoleCol');
    const classCol = document.getElementById('bulkClassCol');
    const bulkForm = document.getElementById('bulkAccessForm');
    const selectedInputs = document.getElementById('bulkSelectedInputs');

    if (scopeSelect) {
        const syncBulkScope = () => {
            roleCol.classList.toggle('d-none', scopeSelect.value !== 'role');
            classCol.classList.toggle('d-none', scopeSelect.value !== 'class');
        };
        scopeSelect.addEventListener('change', syncBulkScope);
        syncBulkScope();
    }

    if (bulkForm && selectedInputs) {
        bulkForm.addEventListener('submit', () => {
            selectedInputs.innerHTML = '';
            document.querySelectorAll('.member-checkbox:checked').forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_ids[]';
                input.value = cb.value;
                selectedInputs.appendChild(input);
            });
        });
    }
});
</script>
@endsection
