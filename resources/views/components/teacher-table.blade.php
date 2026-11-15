<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle teacher-table">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Campus</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Classes and subjects</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($teachers as $teacher)
                    @php
                        $assignments = $teacher->classSubjects
                            ->filter(fn ($assignment) => $assignment->schoolClass && $assignment->subject)
                            ->values();
                        $visibleAssignments = $assignments->take(3);
                        $moreAssignments = $assignments->slice(3);
                        $campusName = $teacher->branch?->name ?? $teacher->user->branch?->name ?? 'Unassigned';
                        $availableBranches = ($branches ?? collect())->where('id', '!=', $teacher->branch_id ?? $teacher->user->branch_id);
                    @endphp
                    <tr>
                        <td class="teacher-name fw-semibold">
                            <a href="{{ route('admin.teachers.show', $teacher) }}" class="text-decoration-none fw-bold text-primary">
                                {{ $teacher->user->name }}
                            </a>
                            @if ($teacher->branchTransfers->isNotEmpty())
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1" title="Has transfer history">
                                    <i class="bi bi-clock-history"></i> Transferred
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                <i class="bi bi-building me-1"></i>{{ $campusName }}
                            </span>
                        </td>
                        <td>{{ $teacher->department?->name ?? 'Not assigned' }}</td>
                        <td>
                            <span class="badge bg-{{ $teacher->status === 'active' ? 'success' : 'secondary' }}">
                                {{ str_replace('_', ' ', ucfirst($teacher->status)) }}
                            </span>
                        </td>
                        <td class="teacher-contact small">{{ $teacher->user->email }}</td>
                        <td class="teacher-contact small">{{ $teacher->phone ?? '—' }}</td>
                        <td>
                            <div class="teacher-assignment-list">
                                @forelse ($visibleAssignments as $assignment)
                                    <span class="badge bg-light text-dark border teacher-assignment-chip">
                                        {{ $assignment->schoolClass->full_name }} &middot; {{ $assignment->subject->name }}
                                    </span>
                                @empty
                                    <span class="text-muted small">Not assigned</span>
                                @endforelse
                                @if ($moreAssignments->isNotEmpty())
                                    <details class="teacher-assignment-overflow">
                                        <summary class="badge bg-secondary">+{{ $moreAssignments->count() }} more</summary>
                                        <div class="teacher-assignment-overflow-list">
                                            @foreach ($moreAssignments as $assignment)
                                                <span class="badge bg-light text-dark border teacher-assignment-chip">
                                                    {{ $assignment->schoolClass->full_name }} &middot; {{ $assignment->subject->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.teachers.show', $teacher) }}" class="btn btn-outline-info" title="View Profile & Salary Details">
                                    <i class="bi bi-person-badge"></i>
                                </a>
                                <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-outline-primary" title="Edit Teacher">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#transferTeacherModal{{ $teacher->id }}" title="Transfer to another campus">
                                    <i class="bi bi-arrow-left-right me-1"></i>Transfer
                                </button>
                                <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" onsubmit="return confirm('Delete this teacher?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" title="Delete Teacher"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>

                            <!-- Teacher Campus Transfer Modal -->
                            <div class="modal fade text-start" id="transferTeacherModal{{ $teacher->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.teachers.transfer', $teacher) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2 text-warning"></i>Transfer Teacher &mdash; {{ $teacher->user->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small text-muted">Current Campus</label>
                                                    <input type="text" class="form-control form-control-sm" value="{{ $campusName }}" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Destination Campus <span class="text-danger">*</span></label>
                                                    <select name="to_branch_id" class="form-select form-select-sm" required>
                                                        <option value="">Select target campus</option>
                                                        @foreach($availableBranches as $targetBranch)
                                                            <option value="{{ $targetBranch->id }}">{{ $targetBranch->name }} ({{ $targetBranch->code }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Effective Transfer Date <span class="text-danger">*</span></label>
                                                    <input type="date" name="transfer_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Transfer Reason / Remarks</label>
                                                    <textarea name="reason" class="form-control form-control-sm" rows="3" placeholder="Faculty re-assignment, campus expansion, or staffing rotation"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-check2-circle me-1"></i>Confirm Campus Transfer</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No teachers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $teachers->links('pagination::bootstrap-5') }}</div>
