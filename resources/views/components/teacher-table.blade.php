<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle teacher-table">
            <colgroup>
                <col class="teacher-col-name">
                <col class="teacher-col-department">
                <col class="teacher-col-status">
                <col class="teacher-col-email">
                <col class="teacher-col-phone">
                <col class="teacher-col-subjects">
                <col class="teacher-col-actions">
            </colgroup>
            <thead>
                <tr>
                    <th>Name</th>
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
                    @endphp
                    <tr>
                        <td class="teacher-name">{{ $teacher->user->name }}</td>
                        <td>{{ $teacher->department?->name ?? 'Not assigned' }}</td>
                        <td><span class="badge bg-{{ $teacher->status === 'active' ? 'success' : 'secondary' }}">{{ str_replace('_', ' ', ucfirst($teacher->status)) }}</span></td>
                        <td class="teacher-contact">{{ $teacher->user->email }}</td>
                        <td class="teacher-contact">{{ $teacher->phone ?? '—' }}</td>
                        <td>
                            <div class="teacher-assignment-list">
                                @forelse ($visibleAssignments as $assignment)
                                    <span class="badge bg-light text-dark border teacher-assignment-chip">
                                        {{ $assignment->schoolClass->full_name }} · {{ $assignment->subject->name }}
                                    </span>
                                @empty
                                    <span class="text-muted">Not assigned</span>
                                @endforelse
                                @if ($moreAssignments->isNotEmpty())
                                    <details class="teacher-assignment-overflow">
                                        <summary class="badge bg-secondary">+{{ $moreAssignments->count() }} more</summary>
                                        <div class="teacher-assignment-overflow-list">
                                            @foreach ($moreAssignments as $assignment)
                                                <span class="badge bg-light text-dark border teacher-assignment-chip">
                                                    {{ $assignment->schoolClass->full_name }} · {{ $assignment->subject->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="teacher-actions">
                                <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                                <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" onsubmit="return confirm('Delete this teacher?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No teachers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $teachers->links('pagination::bootstrap-5') }}</div>
