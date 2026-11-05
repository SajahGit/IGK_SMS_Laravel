@extends('Layouts.LayoutApp')
@section('title', 'Audit Trail')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">System Audit Trail</h3>
        <small class="text-muted">Immutable log of administrative, financial, academic, and security actions across the system.</small>
    </div>
</div>

<!-- Filters Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="user_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected($userId == $u->id)>{{ $u->name }} ({{ $u->role }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="action" class="form-select" onchange="this.form.submit()">
                    <option value="">All Actions</option>
                    @foreach ($actions as $act)
                        <option value="{{ $act }}" @selected($action === $act)>{{ ucwords(str_replace('_', ' ', $act)) }}</option>
                    @endforeach
                </select>
            </div>
            @if ($userId || $action)
                <div class="col-auto">
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary">Reset Filters</a>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Subject Entity</th>
                    <th>IP Address</th>
                    <th class="text-end">Modifications</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="text-nowrap small text-muted">{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                        <td>
                            @if ($log->user)
                                <div class="fw-semibold">{{ $log->user->name }}</div>
                                <span class="badge bg-secondary-subtle text-secondary small">{{ $log->user->role }}</span>
                            @else
                                <span class="text-muted">System Guest</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $actionTone = match($log->action) {
                                    'created', 'published', 'payment_received' => 'success',
                                    'updated', 'unpublished' => 'info',
                                    'deleted', 'payment_reversed' => 'danger',
                                    default => 'primary',
                                };
                            @endphp
                            <span class="badge bg-{{ $actionTone }}-subtle text-{{ $actionTone }} border border-{{ $actionTone }}-subtle">
                                {{ ucwords(str_replace('_', ' ', $log->action)) }}
                            </span>
                        </td>
                        <td>
                            <code class="text-body">{{ class_basename($log->subject_type) }}</code>
                            @if ($log->subject_id)
                                <span class="badge bg-dark-subtle text-body border ms-1">#{{ $log->subject_id }}</span>
                            @endif
                        </td>
                        <td><code class="small">{{ $log->ip_address ?: '—' }}</code></td>
                        <td class="text-end">
                            @if ($log->old_values || $log->new_values)
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}">
                                    <i class="bi bi-code-slash me-1"></i> Diff
                                </button>

                                <!-- Modal -->
                                <div class="modal fade text-start" id="logModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Audit Record #{{ $log->id }} - {{ ucwords(str_replace('_', ' ', $log->action)) }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-2">
                                                    @if ($log->old_values)
                                                        <div class="col-md-6">
                                                            <div class="fw-semibold text-danger mb-1">Previous Values:</div>
                                                            <pre class="bg-body-tertiary p-2 rounded small" style="max-height: 300px; overflow-y:auto;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>
                                                    @endif
                                                    @if ($log->new_values)
                                                        <div class="col-md-{{ $log->old_values ? '6' : '12' }}">
                                                            <div class="fw-semibold text-success mb-1">New Values / Payload:</div>
                                                            <pre class="bg-body-tertiary p-2 rounded small" style="max-height: 300px; overflow-y:auto;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-clock-history fs-2 d-block mb-1"></i>
                            No audit logs recorded for this filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $logs->links('pagination::bootstrap-5') }}</div>
@endsection
