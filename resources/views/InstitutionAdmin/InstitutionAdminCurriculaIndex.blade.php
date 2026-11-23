@extends('Layouts.LayoutApp')
@section('title', 'Curriculum')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Curriculum Builder</h3>
        <small class="text-muted">Assign subjects to programs per term number with complete audit traceability.</small>
    </div>
    @can('create', App\Models\Curriculum::class)
    <a href="{{ route('admin.curricula.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Assign Subject</a>
    @endcan
</div>

@include('Layouts.LayoutErrors')

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 w-100 no-print" id="curriculumTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="structure-tab" data-bs-toggle="tab" data-bs-target="#structure" type="button" role="tab">
            <i class="bi bi-diagram-3 me-1"></i> Curriculum Structure
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="audit-tab" data-bs-toggle="tab" data-bs-target="#audit" type="button" role="tab">
            <i class="bi bi-clock-history me-1"></i> Curriculum Audit Trail
            @if (($curriculumAuditLogs ?? collect())->isNotEmpty())
                <span class="badge bg-secondary ms-1">{{ $curriculumAuditLogs->count() }}</span>
            @endif
        </button>
    </li>
</ul>

<div class="tab-content" id="curriculumTabsContent">
    <!-- Tab 1: Structure -->
    <div class="tab-pane fade show active" id="structure" role="tabpanel">
        <form method="GET" class="row g-2 mb-3 no-print">
            <div class="col-md-3">
                <label class="form-label visually-hidden">Program filter</label>
                <select name="program_id" class="form-select">
                    <option value="">All programs</option>
                    @foreach ($programs as $p)
                        <option value="{{ $p->id }}" @selected($programId == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Show</button>
            </div>
        </form>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Program</th>
                            <th>Subject</th>
                            <th>Term No.</th>
                            <th>Type</th>
                            <th class="text-end" style="min-width: 170px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($curricula as $c)
                        <tr>
                            <td><strong>{{ $c->program->name }}</strong></td>
                            <td>
                                {{ $c->subject->name }}
                                <span class="badge bg-secondary-subtle text-body border ms-1">{{ $c->subject->code }}</span>
                            </td>
                            <td>Term {{ $c->term_number }}</td>
                            <td>
                                <span class="badge {{ $c->is_elective ? 'bg-info' : 'bg-success' }}">
                                    {{ $c->is_elective ? 'Elective' : 'Core' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="table-actions">
                                    @can('update', $c)
                                    <a href="{{ route('admin.curricula.edit', $c) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                                    @endcan
                                    @can('delete', $c)
                                    <form method="POST" action="{{ route('admin.curricula.destroy', $c) }}" class="d-inline m-0" onsubmit="return confirm('Remove this subject assignment?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Remove</button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No curriculum assignments defined.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $curricula->links('pagination::bootstrap-5') }}</div>
    </div>

    <!-- Tab 2: Curriculum Audit Section (Never raw source code) -->
    <div class="tab-pane fade" id="audit" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-shield-check me-2"></i>Curriculum Audit History</span>
                <span class="small text-muted">Immutable log of curriculum assignments and modifications</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Timestamp</th>
                            <th>Authorised User</th>
                            <th>Action</th>
                            <th>Record</th>
                            <th>Summary of Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($curriculumAuditLogs ?? [] as $log)
                        <tr>
                            <td class="text-nowrap small text-muted">
                                {{ $log->created_at->format('d M Y, H:i:s') }}
                            </td>
                            <td>
                                <div class="fw-medium">{{ $log->user?->name ?? 'System' }}</div>
                                <span class="badge bg-secondary-subtle text-secondary small">{{ $log->user?->role ?? 'Admin' }}</span>
                            </td>
                            <td>
                                @php
                                    $actionTone = match($log->action) {
                                        'created_curriculum', 'created' => 'success',
                                        'updated_curriculum', 'updated' => 'info',
                                        'deleted_curriculum', 'deleted' => 'danger',
                                        default => 'primary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $actionTone }}-subtle text-{{ $actionTone }} border border-{{ $actionTone }}-subtle">
                                    {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                </span>
                            </td>
                            <td>
                                <code>Curriculum #{{ $log->subject_id }}</code>
                            </td>
                            <td>
                                @if (!empty($log->new_values) && is_array($log->new_values))
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($log->new_values as $key => $val)
                                            @if (!in_array($key, ['created_at', 'updated_at', 'id']))
                                                <span class="badge bg-light text-dark border">
                                                    {{ ucwords(str_replace('_', ' ', $key)) }}:
                                                    <strong>{{ is_bool($val) ? ($val ? 'Yes' : 'No') : (is_array($val) ? json_encode($val) : ($val ?: '—')) }}</strong>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @elseif (!empty($log->old_values) && is_array($log->old_values))
                                    <span class="badge bg-danger-subtle text-danger border">Removed Record</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-clock-history fs-3 d-block mb-1"></i>
                                No curriculum audit modifications logged yet.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
