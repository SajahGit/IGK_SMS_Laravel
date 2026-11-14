@extends('Layouts.LayoutApp')
@section('title', 'Announcements')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">School Announcements</h3>
        <small class="text-muted">Broadcast notices to teachers, students, guardians, or school-wide.</small>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnouncementModal">
        <i class="bi bi-megaphone-fill me-1"></i> New Announcement
    </button>
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Title & Body</th>
                    <th>Target Audience</th>
                    <th>Published At</th>
                    <th>Expires At</th>
                    <th>Author</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($announcements as $a)
                    <tr>
                        <td style="max-width: 400px;">
                            <div class="fw-bold mb-1">{{ $a->title }}</div>
                            <div class="small text-muted text-truncate">{{ Str::limit($a->body, 120) }}</div>
                        </td>
                        <td>
                            @php
                                $roleBadge = match($a->target_role) {
                                    'all' => 'primary',
                                    'teacher' => 'info',
                                    'student' => 'warning',
                                    'parent' => 'success',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $roleBadge }} text-uppercase">{{ $a->target_role }}</span>
                            @if ($a->program || $a->schoolClass)
                                <div class="small text-muted">{{ $a->program?->name }}{{ $a->schoolClass ? ' / '.$a->schoolClass->full_name : '' }}</div>
                            @endif
                        </td>
                        <td>{{ $a->published_at->format('d M Y, h:i A') }}</td>
                        <td>{{ $a->expires_at ? $a->expires_at->format('d M Y') : 'Never' }}</td>
                        <td>{{ $a->creator?->name ?: 'System' }}</td>
                        <td class="text-end">
                            @can('announcements.update')
                                <a href="{{ route('admin.announcements.edit', $a) }}" class="btn btn-sm btn-outline-primary" title="Edit announcement"><i class="bi bi-pencil-square"></i></a>
                            @endcan
                            @can('announcements.delete')
                                <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" class="d-inline" onsubmit="return confirm('Delete announcement \'{{ $a->title }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete Announcement">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-megaphone fs-2 d-block mb-1"></i>
                            No announcements posted yet. Click "New Announcement" to publish one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $announcements->links('pagination::bootstrap-5') }}</div>

<!-- Create Modal -->
<div class="modal fade" id="createAnnouncementModal" tabindex="-1" aria-labelledby="createAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.announcements.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createAnnouncementModalLabel"><i class="bi bi-megaphone-fill me-2 text-primary"></i>Post Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_title" class="form-label">Notice Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="create_title" class="form-control" maxlength="120" pattern="[A-Za-z][A-Za-z0-9\s\x2d:\x28\x29!]*" data-live-filter="announcement-title" placeholder="e.g. Mid-term examination schedule release" required value="{{ old('title') }}">
                    </div>
                    <div class="mb-3">
                        <label for="create_role" class="form-label">Target Audience <span class="text-danger">*</span></label>
                        <select name="target_role" id="create_role" class="form-select" required>
                            <option value="all" @selected(old('target_role') === 'all')>All School</option>
                            <option value="admin" @selected(old('target_role') === 'admin')>Administrators</option>
                            <option value="registrar" @selected(old('target_role') === 'registrar')>Registrars</option>
                            <option value="teacher" @selected(old('target_role') === 'teacher')>Teachers Only</option>
                            <option value="accountant" @selected(old('target_role') === 'accountant')>Accountants</option>
                            <option value="student" @selected(old('target_role') === 'student')>Students Only</option>
                            <option value="parent" @selected(old('target_role') === 'parent')>Guardians Only</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="create_program" class="form-label">Program (optional)</label>
                            <select name="program_id" id="create_program" class="form-select">
                                <option value="">All programs</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}" @selected(old('program_id') == $program->id)>{{ $program->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="create_class" class="form-label">Section/class (optional)</label>
                            <select name="class_id" id="create_class" class="form-select">
                                <option value="">All sections</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="create_pub" class="form-label">Published At <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="published_at" id="create_pub" class="form-control" required value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-6">
                            <label for="create_exp" class="form-label">Expires At (Optional)</label>
                            <input type="datetime-local" name="expires_at" id="create_exp" class="form-control" value="{{ old('expires_at') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="create_body" class="form-label">Announcement Content <span class="text-danger">*</span></label>
                        <textarea name="body" id="create_body" rows="5" class="form-control" placeholder="Write notice content here..." required>{{ old('body') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill me-1"></i> Post Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
