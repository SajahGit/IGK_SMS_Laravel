@extends('Layouts.LayoutApp')
@section('title', 'Edit Student - ' . $student->user->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Edit Student</h3>
        <small class="text-muted">Manage profile details, enrollment class, and verified documentation.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.students.report-card', $student) }}" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-person me-1"></i> View Report Card
        </a>
        <a href="{{ route('admin.students.report-card.pdf', $student) }}" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Students
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-3">
        <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Student Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.students.update', $student) }}">
            @csrf @method('PUT')
            @include('InstitutionAdmin.InstitutionAdminStudentsForm')
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-3">
        <h5 class="mb-0"><i class="bi bi-journal-check me-2"></i>Subject Enrollments</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>Term</th><th>Class/Section</th><th>Subject</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse ($student->classSubjects as $classSubject)
                    <tr>
                        <td>{{ $classSubject->term?->name ?? 'Legacy offering' }}</td>
                        <td>{{ $classSubject->schoolClass?->full_name ?? '—' }}</td>
                        <td>{{ $classSubject->subject?->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $classSubject->pivot->status === 'enrolled' ? 'success' : 'secondary' }}">{{ ucfirst($classSubject->pivot->status) }}</span></td>
                        <td>
                            @if ($classSubject->pivot->status === 'enrolled')
                                <form method="POST" action="{{ route('admin.students.class-subjects.drop', [$student, $classSubject]) }}" onsubmit="return confirm('Drop {{ $classSubject->subject?->name }} for this student? Existing marks will be retained.')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">Drop subject</button>
                                </form>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No subject enrollments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Student Documents (Private Local Storage) -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><i class="bi bi-folder-check me-2"></i>Student Documents (Private Storage)</h5>
            <small class="text-muted">Files are stored securely on the private local disk and cannot be accessed publicly.</small>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#uploadDocCollapse">
            <i class="bi bi-cloud-arrow-up me-1"></i> Upload Document
        </button>
    </div>

    <!-- Upload Form Collapse -->
    <div class="collapse p-3 bg-body-tertiary border-bottom" id="uploadDocCollapse">
        <form method="POST" action="{{ route('admin.students.documents.upload', $student) }}" enctype="multipart/form-data" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label for="doc_type" class="form-label">Document Type <span class="text-danger">*</span></label>
                <select name="type" id="doc_type" class="form-select" required>
                    <option value="">Select Document Type</option>
                    <option value="birth_cert">Birth Certificate</option>
                    <option value="transcript">Academic Transcript</option>
                    <option value="photo">Student Photograph</option>
                    <option value="id_card">Identity Card / Passport</option>
                    <option value="other">Other Official Document</option>
                </select>
            </div>
            <div class="col-md-5">
                <label for="doc_file" class="form-label">File (PDF, JPG, PNG &bull; Max 5MB) <span class="text-danger">*</span></label>
                <input type="file" name="document" id="doc_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-upload me-1"></i> Upload
                </button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>File Name</th>
                    <th>Size</th>
                    <th>Uploaded Date</th>
                    <th>Uploaded By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($student->documents as $doc)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $doc->type_name }}</span></td>
                        <td>
                            <i class="bi bi-file-earmark-text text-primary me-1"></i>
                            <span class="fw-semibold">{{ $doc->original_name }}</span>
                        </td>
                        <td>{{ $doc->readable_size }}</td>
                        <td>{{ $doc->created_at->format('d M Y, h:i A') }}</td>
                        <td>{{ $doc->uploader?->name ?: 'System' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.students.documents.download', $doc) }}" class="btn btn-outline-primary" title="Download Document">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>
                                <form method="POST" action="{{ route('admin.students.documents.destroy', $doc) }}" class="d-inline" onsubmit="return confirm('Delete document \'{{ $doc->original_name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" title="Delete Document">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-folder-x fs-2 d-block mb-1"></i>
                            No documents uploaded for this student yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
