@extends('Layouts.LayoutApp')
@section('title', 'Bulk import')
@section('content')
<div class="mb-3">
    <h3 class="mb-0">Bulk CSV import</h3>
    <small class="text-muted">Validate the complete file first. Imports are all-or-nothing and capped at 5,000 rows.</small>
</div>
@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label for="entity" class="form-label">Records</label>
                <select id="entity" name="entity" class="form-select" data-import-entity required>
                    <option value="students" @selected(old('entity', $entity ?? '') === 'students')>Students</option>
                    <option value="staff" @selected(old('entity', $entity ?? '') === 'staff')>Staff / teachers</option>
                    <option value="subjects" @selected(old('entity', $entity ?? '') === 'subjects')>Subjects</option>
                    <option value="guardians" @selected(old('entity', $entity ?? '') === 'guardians')>Guardians</option>
                    <option value="inventory" @selected(old('entity', $entity ?? '') === 'inventory')>Inventory items</option>
                    <option value="library_books" @selected(old('entity', $entity ?? '') === 'library_books')>Library books</option>
                </select>
            </div>
            <div class="col-md-5">
                <label for="file" class="form-label">CSV file</label>
                <input id="file" name="file" type="file" accept=".csv,text/csv" class="form-control" required>
                <div class="form-text">Maximum 5 MB. Required columns are listed below.</div>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button class="btn btn-outline-primary" name="mode" value="dry_run">Validate file</button>
                <button class="btn btn-primary" name="mode" value="commit">Import rows</button>
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('admin.imports.template', ['entity' => 'students']) }}"
                   data-import-template-link
                   data-template-url="{{ route('admin.imports.template', ['entity' => 'IMPORT_ENTITY']) }}">
                    <i class="bi bi-download me-1"></i>Download CSV template
                </a>
                <span class="form-text">The template header follows the selected record type; fill required values before validating.</span>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><strong>CSV column formats</strong></div>
    <div class="card-body">
        <p class="mb-2"><strong>Students:</strong> <code>name,email,guardian_id,class_id,document_type,issuing_country,document_number</code> (optional: <code>program_id,academic_year_id,admission_no,admission_date,dob,gender,phone,phone_region,status</code>)</p>
        <p class="mb-2"><strong>Staff / teachers:</strong> <code>name,email</code> (optional: <code>phone,phone_region,department_id,status</code>)</p>
        <p class="mb-2"><strong>Subjects:</strong> <code>name,code</code> (optional: <code>credit_hours</code>)</p>
        <p class="mb-2"><strong>Guardians:</strong> <code>name,email</code> (optional: <code>phone,phone_region</code>)</p>
        <p class="mb-2"><strong>Inventory items:</strong> <code>sku,name,category,unit</code> (optional: <code>quantity_on_hand,reorder_level,description</code>)</p>
        <p class="mb-0"><strong>Library books:</strong> <code>title,author,category</code> (optional: <code>isbn,publication_year,copies</code>)</p>
        <p class="small text-muted mt-3 mb-0">Imported user accounts get a random, undisclosed password and are required to set a new password. Existing user credentials are not modified.</p>
        <p class="small text-muted mt-2 mb-0">The <code>student-register.csv</code> download is an export report, not a student import template. Use the template above; student imports require existing guardian and class IDs plus document details.</p>
    </div>
</div>

@isset($result)
<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <strong>Import validation report</strong>
        @if ($result['rows'] === [])
            <span class="badge bg-danger">{{ $result['file_errors'] === [] ? 'No data rows' : 'File not validated' }}</span>
        @else
            <span>{{ $result['valid_count'] }} valid · {{ $result['invalid_count'] }} invalid</span>
        @endif
    </div>
    <div class="card-body">
        @if ($result['rows'] === [] && $result['file_errors'] !== [])
            <div class="alert alert-warning">
                The CSV could not be validated, so no rows were checked.
                Download the matching template and copy your data into its columns.
                @if (($entity ?? '') === 'students' && str_contains(implode(' ', $result['file_errors']), 'Missing required column'))
                    The student register export does not include guardian IDs, class IDs, or required document details, so it cannot be imported as a new student list.
                @endif
            </div>
            <a class="btn btn-outline-primary mb-3" href="{{ route('admin.imports.template', ['entity' => $entity ?? 'students']) }}">
                <i class="bi bi-download me-1"></i>Download {{ str_replace('_', ' ', ucfirst($entity ?? 'students')) }} import template
            </a>
        @elseif ($result['rows'] === [])
            <div class="alert alert-warning">The CSV has no data rows to import. Add at least one row and validate it again.</div>
        @endif
        @foreach ($result['file_errors'] as $error)
            <div class="alert alert-danger">{{ $error }}</div>
        @endforeach
        @if ($result['can_import'])
            <div class="alert alert-success mb-0">Every row passed validation. Select the CSV again and choose “Import rows” to save it.</div>
        @elseif ($result['rows'] !== [])
            <div class="alert alert-warning">No rows were imported. Correct the reported issues and validate the file again.</div>
        @endif
    </div>
    @if ($result['rows'] !== [])
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>CSV line</th><th>Record</th><th>Issues</th></tr></thead>
            <tbody>
            @foreach ($result['rows'] as $row)
                <tr>
                    <td>{{ $row['line'] }}</td>
                    <td>{{ $row['data']['name'] ?? $row['data']['code'] ?? '—' }}</td>
                    <td>@if ($row['errors'] === [])<span class="text-success">Valid</span>@else<ul class="mb-0">@foreach ($row['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endisset
@endsection
