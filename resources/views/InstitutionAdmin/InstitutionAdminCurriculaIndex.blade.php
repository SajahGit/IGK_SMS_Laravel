@extends('Layouts.LayoutApp')
@section('title', 'Curriculum')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Curriculum Builder</h3>
        <small class="text-muted">Assign subjects to programs per term number.</small>
    </div>
    @can('create', App\Models\Curriculum::class)
    <a href="{{ route('admin.curricula.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Assign Subject</a>
    @endcan
</div>

@include('Layouts.LayoutErrors')

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
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($curricula as $c)
                <tr>
                    <td>{{ $c->program->name }}</td>
                    <td>{{ $c->subject->name }} <span class="badge bg-secondary text-truncate" style="max-width:120px;">{{ $c->subject->code }}</span></td>
                    <td>{{ $c->term_number }}</td>
                    <td>{{ $c->is_elective ? '<span class="badge bg-info">Elective</span>' : '<span class="badge bg-success">Core</span>' }}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            @can('update', $c)
                            <a href="{{ route('admin.curricula.edit', $c) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                            @endcan
                            @can('delete', $c)
                            <form method="POST" action="{{ route('admin.curricula.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Remove this subject assignment?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Remove</button>
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
@endsection
