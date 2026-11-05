@extends('Layouts.LayoutApp')
@section('title', 'Term Results')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="mb-0">Term Results</h3>
        <p class="text-muted mb-0">Generate, review, approve, and publish final subject grades for a term.</p>
    </div>
    <a class="btn btn-outline-primary" href="{{ route('admin.grade-scale.edit') }}">Configure Grade Scale</a>
</div>

@include('Layouts.LayoutErrors')

@if (session('missing_result_pairs'))
    <div class="alert alert-warning">
        <strong>Incomplete student/subject pairs</strong>
        <ul class="mb-0 mt-2">
            @foreach (session('missing_result_pairs') as $pair)
                <li>{{ $pair['admission_no'] }} — {{ $pair['student'] }} / {{ $pair['subject'] }}: {{ implode(', ', $pair['missing_exams']) }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="GET" action="{{ route('admin.term-results.index') }}" class="card card-body mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="term_id" class="form-label">Term</label>
            <select id="term_id" name="term_id" class="form-select">
                @foreach ($terms as $option)
                    <option value="{{ $option->id }}" @selected($term?->id === $option->id)>
                        {{ $option->academicYear->name }} — {{ $option->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <label for="class_id" class="form-label">Class / Section</label>
            <select id="class_id" name="class_id" class="form-select">
                <option value="">All permitted classes</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected((int) $classId === $class->id)>{{ $class->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary" type="submit">Show Results</button>
        </div>
    </div>
</form>

@if ($term && $classId)
    @php($currentStatus = $results->first()?->publication_status)
    <div class="card card-body mb-4">
        @if ($generation)
            <div class="alert {{ $generation->status === 'failed' ? 'alert-danger' : 'alert-info' }} mb-3">
                Generation job: <strong>{{ ucfirst($generation->status) }}</strong>
                @if ($generation->error)
                    <div class="mt-1">{{ $generation->error }}</div>
                @endif
            </div>
        @endif
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.term-results.generate', $term) }}">
                @csrf
                <input type="hidden" name="class_id" value="{{ $classId }}">
                <button class="btn btn-outline-primary" type="submit">Generate / Recalculate Draft</button>
            </form>
            @if ($currentStatus === 'draft')
                <form method="POST" action="{{ route('admin.term-results.submit', $term) }}">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $classId }}">
                    <button class="btn btn-warning" type="submit">Submit for Approval</button>
                </form>
            @endif
            @if ($currentStatus === 'pending_approval')
                <form method="POST" action="{{ route('admin.term-results.publish', $term) }}">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $classId }}">
                    <button class="btn btn-success" type="submit">Approve &amp; Publish</button>
                </form>
            @endif
            @if ($currentStatus === 'published' && auth()->user()->isAdmin())
                <form method="POST" action="{{ route('admin.term-results.unpublish', $term) }}" class="d-flex gap-2">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $classId }}">
                    <input class="form-control" name="reason" minlength="5" maxlength="1000" required placeholder="Reason to unpublish">
                    <button class="btn btn-outline-danger text-nowrap" type="submit">Return to Draft</button>
                </form>
            @endif
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Subject grades</th>
                    <th class="text-center">GPA</th>
                    <th class="text-center">Result</th>
                    <th class="text-center">Publication</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($results as $result)
                    @php($key = $result->term_id.':'.$result->class_id.':'.$result->student_id)
                    <tr>
                        <td><span class="fw-semibold">{{ $result->student->user->name }}</span><br><code>{{ $result->student->admission_no }}</code></td>
                        <td>{{ $result->schoolClass->full_name }}</td>
                        <td>
                            @foreach ($subjectResults->get($key, collect()) as $subjectResult)
                                <span class="badge text-bg-light border me-1 mb-1">
                                    {{ $subjectResult->subject->code }}:
                                    {{ $subjectResult->result_status === 'graded' ? $subjectResult->grade : ucfirst($subjectResult->result_status) }}
                                </span>
                            @endforeach
                        </td>
                        <td class="text-center">{{ $result->gpa === null ? '—' : number_format((float) $result->gpa, 2) }}</td>
                        <td class="text-center">{{ ucfirst($result->result_status) }}</td>
                        <td class="text-center">{{ str_replace('_', ' ', ucfirst($result->publication_status)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No term results have been generated for this selection.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (method_exists($results, 'links'))
        <div class="card-footer">{{ $results->links() }}</div>
    @endif
</div>
@endsection
