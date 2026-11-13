@extends('Layouts.LayoutApp')
@section('title', 'Leave management')
@section('content')
<div class="mb-3"><h3 class="mb-0">Leave management</h3><small class="text-muted">Submit employee leave requests and record an approval decision.</small></div>
@include('Layouts.LayoutErrors')
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card shadow-sm border-0 mb-3"><div class="card-header bg-transparent"><strong>New leave request</strong></div><div class="card-body">
    <form method="POST" action="{{ route('admin.hr.leaves.store') }}" class="row g-3">
        @csrf
        <div class="col-md-4"><label class="form-label">Employee</label><select class="form-select" name="employee_id" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->employee_no }} — {{ $employee->user?->name ?? $employee->teacher?->user?->name ?? $employee->job_title }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Leave type</label><select class="form-select" name="leave_type" required>@foreach(['annual', 'sick', 'unpaid', 'other'] as $type)<option value="{{ $type }}" @selected(old('leave_type') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Start</label><input class="form-control" type="date" name="start_date" value="{{ old('start_date') }}" required></div>
        <div class="col-md-2"><label class="form-label">End</label><input class="form-control" type="date" name="end_date" value="{{ old('end_date') }}" required></div>
        <div class="col-md-12"><label class="form-label">Reason</label><textarea class="form-control" name="reason" rows="2" maxlength="500" required>{{ old('reason') }}</textarea></div>
        <div class="col-12"><button class="btn btn-primary">Submit request</button></div>
    </form>
</div></div>
<div class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Reason</th><th>Status</th><th>Decision</th></tr></thead>
    <tbody>
    @forelse($leaveRequests as $leave)
        <tr><td>{{ $leave->employee?->employee_no ?? 'Unknown employee' }} — {{ $leave->employee?->user?->name ?? $leave->employee?->teacher?->user?->name ?? $leave->employee?->job_title ?? '—' }}</td><td class="whitespace-nowrap">{{ ucfirst($leave->leave_type) }}</td>
            <td>{{ $leave->start_date->format('Y-m-d') }} – {{ $leave->end_date->format('Y-m-d') }}</td><td>{{ $leave->days }}</td><td>{{ $leave->reason }}</td>
            <td><span class="badge bg-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning text-dark') }}">{{ ucfirst($leave->status) }}</span></td>
            <td>
                @if($leave->status === 'pending')
                <form method="POST" action="{{ route('admin.hr.leaves.review', $leave) }}" class="d-flex flex-wrap gap-1">
                    @csrf <input type="text" name="review_note" class="form-control form-control-sm" placeholder="Decision note">
                    <button class="btn btn-sm btn-success" name="decision" value="approved">Approve</button><button class="btn btn-sm btn-outline-danger" name="decision" value="rejected">Reject</button>
                </form>
                @else{{ $leave->reviewer?->name ?? '—' }}@if($leave->review_note)<div class="small text-muted">{{ $leave->review_note }}</div>@endif @endif
            </td>
        </tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">No leave requests yet.</td></tr>@endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $leaveRequests->links('pagination::bootstrap-5') }}</div>
@endsection
